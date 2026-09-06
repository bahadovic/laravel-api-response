<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

interface ApiResponseInterface
{
    public function make(): ResponseBuilderInterface;

    /** @param array<string, mixed> $headers */
    public function success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $headers = []): JsonResponse;

    /** @param array<string, mixed> $headers */
    public function error(?string $message = null, int $statusCode = 400, mixed $errors = null, array $headers = []): JsonResponse;

    /** @param array<string, mixed> $headers */
    public function created(mixed $data = null, ?string $message = null, array $headers = []): JsonResponse;

    /** @param array<string, mixed> $headers */
    public function noContent(array $headers = []): Response;

    /** @param array<string, mixed> $headers */
    public function validationError(mixed $errors, ?string $message = null, array $headers = []): JsonResponse;

    /** @param array<string, mixed> $headers */
    public function unauthorized(?string $message = null, array $headers = []): JsonResponse;

    /** @param array<string, mixed> $headers */
    public function forbidden(?string $message = null, array $headers = []): JsonResponse;

    /** @param array<string, mixed> $headers */
    public function notFound(?string $message = null, array $headers = []): JsonResponse;
}
