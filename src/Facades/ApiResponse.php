<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Bahadovic\ApiResponse\ResponseBuilder make()
 * @method static \Bahadovic\ApiResponse\ResponseBuilder data(mixed $data)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder setData(mixed $data)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder message(?string $message)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder setMessage(?string $message)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder status(int $statusCode)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder setStatusCode(int $statusCode)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder header(string $key, string $value)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder withHeaders(array $headers)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder meta(array $meta)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder withMeta(array $meta)
 * @method static \Bahadovic\ApiResponse\ResponseBuilder errors(mixed $errors)
 * @method static \Illuminate\Http\JsonResponse send()
 * @method static \Illuminate\Http\JsonResponse success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $headers = [])
 * @method static \Illuminate\Http\JsonResponse created(mixed $data = null, ?string $message = null, array $headers = [])
 * @method static \Symfony\Component\HttpFoundation\Response noContent(array $headers = [])
 * @method static \Illuminate\Http\JsonResponse error(?string $message = null, int $statusCode = 400, mixed $errors = null, array $headers = [])
 * @method static \Illuminate\Http\JsonResponse validationError(mixed $errors, ?string $message = null, array $headers = [])
 * @method static \Illuminate\Http\JsonResponse notFound(?string $message = 'Resource not found.', array $headers = [])
 * @method static \Illuminate\Http\JsonResponse unauthorized(?string $message = 'Unauthenticated.', array $headers = [])
 * @method static \Illuminate\Http\JsonResponse forbidden(?string $message = 'Forbidden.', array $headers = [])
 *
 * @see \Bahadovic\ApiResponse\ApiResponse
 */
class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'api-response';
    }
}
