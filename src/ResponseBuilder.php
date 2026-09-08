<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse;

use Bahadovic\ApiResponse\Contracts\ResponseBuilderInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ResponseBuilder implements ResponseBuilderInterface
{
    use Macroable;

    protected mixed $data = null;

    protected ?string $message = null;

    protected int $statusCode = Response::HTTP_OK;

    /** @var array<string, mixed> */
    protected array $headers = [];

    /** @var array<string, mixed> */
    protected array $meta = [];

    protected mixed $errors = null;

    protected bool $success = true;

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setData(mixed $data): self
    {
        return $this->data($data);
    }

    public function message(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function setMessage(?string $message): self
    {
        return $this->message($message);
    }

    public function status(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        return $this->status($statusCode);
    }

    public function markAsError(): self
    {
        $this->success = false;

        return $this;
    }

    public function markAsSuccess(): self
    {
        $this->success = true;

        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = [...$this->headers, ...$headers];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): self
    {
        $this->meta = [...$this->meta, ...$meta];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withMeta(array $meta): self
    {
        return $this->meta($meta);
    }

    public function errors(mixed $errors): self
    {
        $this->success = false;

        if ($errors instanceof ValidationException) {
            $this->errors = $errors->errors();
        } elseif ($errors instanceof MessageProvider) {
            $this->errors = $errors->getMessageBag()->toArray();
        } elseif ($errors instanceof Arrayable) {
            $this->errors = $errors->toArray();
        } else {
            $this->errors = $errors;
        }

        return $this;
    }

    public function send(): JsonResponse
    {
        if ($this->statusCode >= 400) {
            $this->success = false;
        }

        /** @var array<string, mixed> $config */
        $config = (array) config('api-response', []);

        /** @var array<string, string> $keys */
        $keys = (array) ($config['keys'] ?? []);
        /** @var array<string, string> $messages */
        $messages = (array) ($config['messages'] ?? []);

        $successKey = $keys['success'] ?? 'success';
        $messageKey = $keys['message'] ?? 'message';
        $dataKey = $keys['data'] ?? 'data';
        $errorsKey = $keys['errors'] ?? 'errors';
        $metaKey = $keys['meta'] ?? 'meta';

        $defaultSuccessMessage = $messages['success'] ?? 'Operation completed successfully.';
        $defaultErrorMessage = $messages['error'] ?? 'An error occurred while processing the request.';

        if (! $this->success) {
            $payload = [
                $successKey => false,
                $messageKey => $this->message ?? $defaultErrorMessage,
            ];

            if ($this->data !== null) {
                $payload[$dataKey] = $this->data;
            }

            if ($this->errors !== null) {
                $payload[$errorsKey] = $this->errors;
            }

            if (! empty($this->meta)) {
                $payload[$metaKey] = $this->meta;
            }

            return new JsonResponse($payload, $this->statusCode, $this->headers);
        }

        if ($this->data instanceof JsonResource) {
            $resource = clone $this->data;
            $existingAdditional = (array) $resource->additional;

            $additionalData = array_merge($existingAdditional, [
                $successKey => true,
                $messageKey => $this->message ?? $defaultSuccessMessage,
            ]);

            if (! empty($this->meta)) {
                $existingMeta = isset($additionalData[$metaKey]) && is_array($additionalData[$metaKey])
                    ? (array) $additionalData[$metaKey]
                    : [];

                $additionalData[$metaKey] = array_merge($existingMeta, $this->meta);
            }

            $request = request();

            return $resource
                ->additional($additionalData)
                ->toResponse($request)
                ->setStatusCode($this->statusCode)
                ->withHeaders($this->headers);
        }

        if (
            $this->data instanceof LengthAwarePaginator
            || $this->data instanceof Paginator
            || $this->data instanceof CursorPaginator
        ) {
            $paginationMeta = $this->formatPaginationMeta($this->data);

            return new JsonResponse([
                $successKey => true,
                $messageKey => $this->message ?? $defaultSuccessMessage,
                $dataKey => $this->data->items(),
                $metaKey => array_merge($this->meta, $paginationMeta),
            ], $this->statusCode, $this->headers);
        }

        $resolvedData = $this->data;
        if ($resolvedData instanceof Arrayable) {
            $resolvedData = $resolvedData->toArray();
        } elseif ($resolvedData instanceof Jsonable) {
            $resolvedData = json_decode($resolvedData->toJson(), true);
        }

        $payload = [
            $successKey => true,
            $messageKey => $this->message ?? $defaultSuccessMessage,
            $dataKey => $resolvedData,
        ];

        if (! empty($this->meta)) {
            $payload[$metaKey] = $this->meta;
        }

        return new JsonResponse($payload, $this->statusCode, $this->headers);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatPaginationMeta(mixed $paginator): array
    {
        if ($paginator instanceof LengthAwarePaginator) {
            return [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ];
        }

        if ($paginator instanceof Paginator) {
            return [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ];
        }

        if ($paginator instanceof CursorPaginator) {
            return [
                'per_page' => $paginator->perPage(),
                'has_more' => method_exists($paginator, 'hasMorePages') ? (bool) $paginator->hasMorePages() : false,
                'cursor' => [
                    'current' => $paginator->cursor()?->encode(),
                    'next' => $paginator->nextCursor()?->encode(),
                    'prev' => $paginator->previousCursor()?->encode(),
                ],
            ];
        }

        return [];
    }
}
