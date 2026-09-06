<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse;

use Bahadovic\ApiResponse\Contracts\ApiResponseInterface;
use Bahadovic\ApiResponse\Contracts\ResponseBuilderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Traits\Macroable;

class ApiResponse implements ApiResponseInterface
{
    use Macroable {
        macro as protected parentMacro;
    }

    /**
     * ثبت ماکرو به صورت همزمان روی ApiResponse و ResponseBuilder
     */
    public static function macro(string $name, object|callable $macro): void
    {
        static::parentMacro($name, $macro);
        ResponseBuilder::macro($name, $macro);
    }

    public function make(): ResponseBuilderInterface
    {
        return new ResponseBuilder;
    }

    /**
     * @param  string  $method
     * @param  array<int|string, mixed>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            $macro = static::$macros[$method];

            if ($macro instanceof \Closure) {
                $macro = $macro->bindTo($this, static::class);
            }

            return $macro(...$parameters);
        }

        return $this->make()->{$method}(...$parameters);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $headers = []): JsonResponse
    {
        return $this->make()->data($data)->message($message)->status($statusCode)->withHeaders($headers)->send();
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function error(?string $message = null, int $statusCode = 400, mixed $errors = null, array $headers = []): JsonResponse
    {
        return $this->make()->message($message)->status($statusCode)->errors($errors)->withHeaders($headers)->send();
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function created(mixed $data = null, ?string $message = null, array $headers = []): JsonResponse
    {
        $default = (string) config('api-response.messages.created', 'Resource created successfully.');

        return $this->success($data, $message ?? $default, 201, $headers);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function noContent(array $headers = []): Response
    {
        return new Response('', 204, $headers);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function validationError(mixed $errors, ?string $message = null, array $headers = []): JsonResponse
    {
        $default = (string) config('api-response.messages.validation_error', 'The given data was invalid.');

        return $this->error($message ?? $default, 422, $errors, $headers);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function unauthorized(?string $message = null, array $headers = []): JsonResponse
    {
        $default = (string) config('api-response.messages.unauthorized', 'Unauthenticated.');

        return $this->error($message ?? $default, 401, null, $headers);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function forbidden(?string $message = null, array $headers = []): JsonResponse
    {
        $default = (string) config('api-response.messages.forbidden', 'Forbidden.');

        return $this->error($message ?? $default, 403, null, $headers);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function notFound(?string $message = null, array $headers = []): JsonResponse
    {
        $default = (string) config('api-response.messages.not_found', 'Resource not found.');

        return $this->error($message ?? $default, 404, null, $headers);
    }
}
