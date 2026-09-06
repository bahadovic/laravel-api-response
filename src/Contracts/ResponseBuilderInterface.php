<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse\Contracts;

use Illuminate\Http\JsonResponse;

interface ResponseBuilderInterface
{
    public function data(mixed $data): self;

    public function message(?string $message): self;

    public function status(int $statusCode): self;

    public function markAsError(): self;

    public function markAsSuccess(): self;

    /** @param array<string, mixed> $headers */
    public function withHeaders(array $headers): self;

    /** @param array<string, mixed> $meta */
    public function withMeta(array $meta): self;

    public function errors(mixed $errors): self;

    public function send(): JsonResponse;
}
