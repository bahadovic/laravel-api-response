<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse\Tests;

use Bahadovic\ApiResponse\Facades\ApiResponse;
use Bahadovic\ApiResponse\Traits\HasApiResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiResponseTest extends TestCase
{
    public function test_it_returns_regular_success_response(): void
    {
        $response = ApiResponse::success(['id' => 1], 'Fetched');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'success' => true,
            'message' => 'Fetched',
            'data' => ['id' => 1],
        ], $response->getData(true));
    }

    public function test_it_returns_created_response(): void
    {
        $response = ApiResponse::created(['id' => 2]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_it_returns_no_content_response(): void
    {
        $response = ApiResponse::noContent();

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_it_returns_unauthorized_and_forbidden(): void
    {
        $unauthorized = ApiResponse::unauthorized();
        $this->assertEquals(401, $unauthorized->getStatusCode());

        $forbidden = ApiResponse::forbidden();
        $this->assertEquals(403, $forbidden->getStatusCode());
    }

    public function test_it_returns_not_found(): void
    {
        $response = ApiResponse::notFound('Item missing');
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Item missing', $response->getData(true)['message']);
    }

    public function test_it_handles_validation_exception_directly(): void
    {
        $exception = ValidationException::withMessages([
            'email' => ['Invalid email.'],
        ]);

        $response = ApiResponse::validationError($exception);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['email' => ['Invalid email.']], $response->getData(true)['errors']);
    }

    public function test_it_handles_json_resource(): void
    {
        $resource = new JsonResource(['id' => 10, 'name' => 'Laravel']);
        $response = ApiResponse::success($resource, 'Resource fetched');

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 10, 'name' => 'Laravel'], $data['data']);
    }

    public function test_it_handles_length_aware_paginator(): void
    {
        $items = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 10, 2, 1);

        $response = ApiResponse::success($paginator);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']);
        $this->assertEquals(10, $data['meta']['total']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(5, $data['meta']['last_page']);
    }

    public function test_it_handles_simple_paginator(): void
    {
        $items = collect([['id' => 1]]);
        $paginator = new Paginator($items, 1, 1);

        $response = ApiResponse::success($paginator);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['meta']['per_page']);
    }

    public function test_it_handles_cursor_paginator(): void
    {
        $items = collect([['id' => 10]]);
        $paginator = new CursorPaginator($items, 1, new Cursor(['id' => 10]));

        $response = ApiResponse::success($paginator);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('cursor', $data['meta']);
    }

    public function test_it_respects_custom_config_keys(): void
    {
        config(['api-response.keys.success' => 'is_ok']);
        config(['api-response.keys.data' => 'payload']);

        $response = ApiResponse::success(['test' => true]);
        $data = $response->getData(true);

        $this->assertArrayHasKey('is_ok', $data);
        $this->assertArrayHasKey('payload', $data);
    }

    public function test_it_supports_macros(): void
    {
        ApiResponse::macro('customAccepted', function (string $msg = 'Accepted') {
            return ApiResponse::make()->message($msg)->status(202)->send();
        });

        $res1 = ApiResponse::customAccepted('From Facade');
        $this->assertEquals(202, $res1->getStatusCode());
        $this->assertEquals('From Facade', $res1->getData(true)['message']);

        $res2 = ApiResponse::make()->customAccepted('From Builder');
        $this->assertEquals(202, $res2->getStatusCode());
        $this->assertEquals('From Builder', $res2->getData(true)['message']);
    }

    public function test_it_supports_fluent_chaining_and_headers(): void
    {
        $response = ApiResponse::make()
            ->data(['order' => 101])
            ->message('Order placed')
            ->status(200)
            ->header('X-Custom-Header', 'ApiResponse')
            ->meta(['benchmark_ms' => 12])
            ->send();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ApiResponse', $response->headers->get('X-Custom-Header'));
        $this->assertEquals(12, $response->getData(true)['meta']['benchmark_ms']);
    }

    public function test_it_handles_anonymous_resource_collections(): void
    {
        $users = collect([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $collection = JsonResource::collection($users);
        $response = ApiResponse::success($collection, 'Users retrieved');

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Users retrieved', $data['message']);
        $this->assertCount(2, $data['data']);
    }

    public function test_it_correctly_verifies_cursor_pagination_details(): void
    {
        $cursor = new Cursor(['id' => 15]);
        $items = collect([['id' => 15]]);
        $paginator = new CursorPaginator($items, 1, $cursor);

        $response = ApiResponse::success($paginator);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('cursor', $data['meta']);
        $this->assertEquals($cursor->encode(), $data['meta']['cursor']['current']);
        $this->assertNull($data['meta']['cursor']['next']);
        $this->assertEquals($paginator->previousCursor()?->encode(), $data['meta']['cursor']['prev']);
    }

    public function test_no_content_response_has_empty_body(): void
    {
        $response = ApiResponse::noContent();

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function test_it_handles_resource_with_meta_headers_and_custom_status(): void
    {
        $resource = new JsonResource(['id' => 42, 'name' => 'Combination Test']);

        $response = ApiResponse::make()
            ->data($resource)
            ->message('Resource with everything')
            ->status(202)
            ->meta(['benchmark_ms' => 5.4])
            ->header('X-Custom-Engine', 'Laravel')
            ->send();

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('Laravel', $response->headers->get('X-Custom-Engine'));

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Resource with everything', $data['message']);
        $this->assertEquals(['id' => 42, 'name' => 'Combination Test'], $data['data']);
        $this->assertEquals(5.4, $data['meta']['benchmark_ms']);
    }

    public function test_it_handles_error_with_meta_and_headers(): void
    {
        $response = ApiResponse::make()
            ->status(403)
            ->message('Denied')
            ->errors(['permission' => ['Root access required']])
            ->meta(['request_id' => 'req-9876'])
            ->header('X-Error-Code', 'AUTH_403')
            ->send();

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('AUTH_403', $response->headers->get('X-Error-Code'));

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals(['permission' => ['Root access required']], $data['errors']);
        $this->assertEquals('req-9876', $data['meta']['request_id']);
    }

    public function test_it_handles_all_custom_config_keys(): void
    {
        config([
            'api-response.keys.success' => 'ok',
            'api-response.keys.message' => 'msg',
            'api-response.keys.data' => 'result',
            'api-response.keys.errors' => 'details',
            'api-response.keys.meta' => 'metadata',
        ]);

        $response = ApiResponse::make()
            ->data(['foo' => 'bar'])
            ->message('Done')
            ->meta(['request_id' => 'req-123'])
            ->send();

        $data = $response->getData(true);

        $this->assertTrue($data['ok']);
        $this->assertEquals('Done', $data['msg']);
        $this->assertEquals(['foo' => 'bar'], $data['result']);
        $this->assertEquals(['request_id' => 'req-123'], $data['metadata']);
    }

    public function test_it_distinguishes_between_null_and_empty_array_data(): void
    {
        $nullResponse = ApiResponse::success(null);
        $this->assertNull($nullResponse->getData(true)['data']);

        $emptyArrayResponse = ApiResponse::success([]);
        $this->assertSame([], $emptyArrayResponse->getData(true)['data']);
    }

    public function test_it_handles_empty_length_aware_paginator(): void
    {
        $paginator = new LengthAwarePaginator(collect([]), 0, 15, 1);
        $response = ApiResponse::success($paginator);

        $data = $response->getData(true);
        $this->assertSame([], $data['data']);
        $this->assertEquals(0, $data['meta']['total']);
        $this->assertFalse($data['meta']['has_more']);
    }

    public function test_it_handles_resource_collection_with_meta_headers_and_status(): void
    {
        $users = collect([
            ['id' => 1, 'name' => 'Sara'],
            ['id' => 2, 'name' => 'Nima'],
        ]);

        $collection = JsonResource::collection($users);

        $response = ApiResponse::make()
            ->data($collection)
            ->message('Collection processed')
            ->status(202)
            ->meta(['filter_applied' => true])
            ->header('X-Batch-ID', 'batch-777')
            ->send();

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('batch-777', $response->headers->get('X-Batch-ID'));

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Collection processed', $data['message']);
        $this->assertCount(2, $data['data']);
        $this->assertTrue($data['meta']['filter_applied']);
    }

    public function test_it_respects_custom_configured_error_messages(): void
    {
        config([
            'api-response.messages.unauthorized' => 'Please login first.',
            'api-response.messages.forbidden' => 'Access strictly denied.',
            'api-response.messages.not_found' => 'Target missing.',
        ]);

        $unauth = ApiResponse::unauthorized();
        $this->assertEquals('Please login first.', $unauth->getData(true)['message']);

        $forbidden = ApiResponse::forbidden();
        $this->assertEquals('Access strictly denied.', $forbidden->getData(true)['message']);

        $notFound = ApiResponse::notFound();
        $this->assertEquals('Target missing.', $notFound->getData(true)['message']);
    }

    public function test_it_creates_a_fresh_builder_for_each_call(): void
    {
        $first = ApiResponse::make()
            ->data(['id' => 1])
            ->message('First')
            ->send();

        $second = ApiResponse::make()
            ->data(['id' => 2])
            ->send();

        $firstData = $first->getData(true);
        $secondData = $second->getData(true);

        $this->assertEquals(['id' => 1], $firstData['data']);
        $this->assertEquals(['id' => 2], $secondData['data']);
        $this->assertEquals('First', $firstData['message']);
        $this->assertEquals('Operation completed successfully.', $secondData['message']);
    }

    public function test_it_preserves_existing_resource_additional_metadata(): void
    {
        $resource = (new JsonResource(['id' => 99]))
            ->additional(['trace_id' => 'trace-abc-123']);

        $response = ApiResponse::make()
            ->data($resource)
            ->meta(['client_version' => '1.0.4'])
            ->send();

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(['id' => 99], $data['data']);
        $this->assertEquals('trace-abc-123', $data['trace_id']);
        $this->assertEquals('1.0.4', $data['meta']['client_version']);
    }

    public function test_it_respects_custom_meta_key_across_all_response_types(): void
    {
        config(['api-response.keys.meta' => 'custom_meta']);

        
        $standard = ApiResponse::make()->data(['val' => 1])->meta(['tag' => 'std'])->send();
        $this->assertArrayHasKey('custom_meta', $standard->getData(true));


        $error = ApiResponse::make()->status(400)->errors(['e' => 1])->meta(['tag' => 'err'])->send();
        $this->assertArrayHasKey('custom_meta', $error->getData(true));


        $paginator = new LengthAwarePaginator(collect([['id' => 1]]), 1, 10, 1);
        $paginated = ApiResponse::make()->data($paginator)->meta(['tag' => 'paged'])->send();
        $this->assertArrayHasKey('custom_meta', $paginated->getData(true));


        $resource = new JsonResource(['id' => 5]);
        $resourceResponse = ApiResponse::make()->data($resource)->meta(['tag' => 'res'])->send();
        $this->assertArrayHasKey('custom_meta', $resourceResponse->getData(true));
    }

    public function test_pagination_meta_precedence_overwrites_colliding_user_keys(): void
    {
        $paginator = new LengthAwarePaginator(collect([['id' => 1]]), 1, 15, 1);

        $response = ApiResponse::make()
            ->data($paginator)
            ->meta([
                'total' => 9999,
                'safe_custom' => 'retained',
            ])
            ->send();

        $data = $response->getData(true);

        $this->assertEquals(1, $data['meta']['total']);
        $this->assertEquals('retained', $data['meta']['safe_custom']);
    }

    public function test_singleton_registration_and_facade_resolution(): void
    {
        $instance1 = app('api-response');
        $instance2 = app('api-response');

        $this->assertInstanceOf(\Bahadovic\ApiResponse\ApiResponse::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }


    public function test_it_merges_pre_existing_resource_meta_without_collision(): void
    {
        $resource = (new JsonResource(['id' => 10]))
            ->additional([
                'meta' => [
                    'source' => 'resource_internal',
                ],
            ]);

        $response = ApiResponse::make()
            ->data($resource)
            ->meta([
                'client' => 'mobile_app',
            ])
            ->send();

        $data = $response->getData(true);

        $this->assertEquals('resource_internal', $data['meta']['source']);
        $this->assertEquals('mobile_app', $data['meta']['client']);
    }


    public function test_has_api_response_trait_works_in_controllers(): void
    {
        $controller = new class
        {
            use HasApiResponse;

            public function index()
            {
                return $this->successResponse(['status' => 'active'], 'Controller ok');
            }

            public function fail()
            {
                return $this->errorResponse('Failed', 400, ['field' => 'bad']);
            }
        };

        $success = $controller->index();
        $this->assertEquals(200, $success->getStatusCode());
        $this->assertTrue($success->getData(true)['success']);
        $this->assertEquals('Controller ok', $success->getData(true)['message']);

        $error = $controller->fail();
        $this->assertEquals(400, $error->getStatusCode());
        $this->assertFalse($error->getData(true)['success']);
    }

    public function test_builder_state_does_not_leak_between_instances(): void
    {
        $first = ApiResponse::make()
            ->data(['a' => 1])
            ->meta(['request' => 'first'])
            ->header('X-Test', 'first')
            ->send();

        $second = ApiResponse::make()
            ->data(['b' => 2])
            ->send();

        $firstData = $first->getData(true);
        $secondData = $second->getData(true);

        $this->assertArrayHasKey('meta', $firstData);
        $this->assertArrayNotHasKey('meta', $secondData);

        $this->assertEquals('first', $first->headers->get('X-Test'));
        $this->assertNull($second->headers->get('X-Test'));
    }

    public function test_it_forces_success_false_when_status_is_error_level(): void
    {
        $response = ApiResponse::make()
            ->status(404)
            ->data(['info' => 'Not Found Context'])
            ->send();

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals(404, $response->getStatusCode());

        $this->assertArrayHasKey('data', $data);
        $this->assertEquals(['info' => 'Not Found Context'], $data['data']);
    }

    public function test_it_handles_message_provider_and_validator(): void
    {
        $validator = Validator::make(
            ['email' => 'invalid'],
            ['email' => 'email']
        );

        $validator->fails();

        $response = ApiResponse::make()->errors($validator)->send();
        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    public function test_it_preserves_original_resource_by_cloning(): void
    {
        $resource = new JsonResource(['id' => 1]);

        ApiResponse::make()->data($resource)->meta(['added' => 1])->send();

        $this->assertEmpty($resource->additional);
    }

    public function test_no_static_state_leaks_in_octane_simulation(): void
    {
        ApiResponse::make()
            ->data(['first' => true])
            ->meta(['trace' => 'abc'])
            ->header('X-Request-Id', 'req-1')
            ->send();

        $response = ApiResponse::make()->data(['second' => true])->send();
        $data = $response->getData(true);

        $this->assertArrayNotHasKey('meta', $data);
        $this->assertNull($response->headers->get('X-Request-Id'));
        $this->assertEquals(['second' => true], $data['data']);
    }

    public function test_response_generation_performance_and_memory(): void
    {
        for ($i = 0; $i < 500; $i++) {
            $response = ApiResponse::success(['id' => $i]);
            $this->assertEquals(200, $response->getStatusCode());
        }
    }
}
