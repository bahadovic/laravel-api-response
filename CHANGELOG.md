# Changelog

All notable changes to `laravel-api-response` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-09-06

### Added
- Stateless `ResponseBuilder` design suitable for long-running Laravel runtimes (Octane, RoadRunner, Swoole).
- Native support for Laravel `JsonResource` and `ResourceCollection`.
- Support for Laravel `LengthAwarePaginator`, `Paginator`, and `CursorPaginator`.
- Fluent chaining interface via `ApiResponse::make()`.
- Configurable response keys (including custom metadata key) and unified fallback messages.
- Pagination metadata precedence protection against user key collisions.
- Full compatibility with Laravel 10.x, 11.x, and 12.x.