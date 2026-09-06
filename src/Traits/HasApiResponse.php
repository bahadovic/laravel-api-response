<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse\Traits;

use Bahadovic\ApiResponse\Facades\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait HasApiResponse
{
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        return ApiResponse::success($data, $message, $statusCode, $headers);
    }

    protected function createdResponse(
        mixed $data = null,
        ?string $message = null,
        array $headers = []
    ): JsonResponse {
        return ApiResponse::created($data, $message, $headers);
    }

    protected function noContentResponse(array $headers = []): Response
    {
        return ApiResponse::noContent($headers);
    }

    protected function errorResponse(
        ?string $message = null,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        return ApiResponse::error($message, $statusCode, $errors, $headers);
    }

    protected function validationErrorResponse(
        mixed $errors,
        ?string $message = null,
        array $headers = []
    ): JsonResponse {
        return ApiResponse::validationError($errors, $message, $headers);
    }

    protected function notFoundResponse(?string $message = null, array $headers = []): JsonResponse
    {
        return ApiResponse::notFound($message, $headers);
    }

    protected function unauthorizedResponse(?string $message = null, array $headers = []): JsonResponse
    {
        return ApiResponse::unauthorized($message, $headers);
    }

    protected function forbiddenResponse(?string $message = null, array $headers = []): JsonResponse
    {
        return ApiResponse::forbidden($message, $headers);
    }
}
