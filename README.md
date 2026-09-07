# Laravel API Response Formatter

<p align="center">
  <a href="https://packagist.org/packages/bahadovic/laravel-api-response"><img src="https://img.shields.io/packagist/v/bahadovic/laravel-api-response.svg?style=flat-square&color=blue" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/bahadovic/laravel-api-response"><img src="https://img.shields.io/packagist/dt/bahadovic/laravel-api-response.svg?style=flat-square&color=green" alt="Total Downloads"></a>
  <a href="https://github.com/bahadovic/laravel-api-response/actions"><img src="https://img.shields.io/github/actions/workflow/status/bahadovic/laravel-api-response/run-tests.yml?branch=main&label=tests&style=flat-square" alt="Build Status"></a>
  <a href="https://phpstan.org/"><img src="https://img.shields.io/badge/PHPStan-Level%208-brightgreen?style=flat-square" alt="PHPStan Level 8"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="License"></a>
</p>

A fluent, modern, and highly-flexible API response builder for Laravel. Designed for minimal runtime overhead, strict typing, and native support for Eloquent Resources and all Laravel Pagination types.

---

## 🚀 Features

- **Standardized Output:** Unified format for success, errors, and pagination.
- **Fluent & Static Interface:** Use clean one-liners or fluent chainable methods.
- **Native Eloquent Resources:** Native support for `JsonResource` and `ResourceCollection`, while preserving Laravel resource behavior.
- **Supported Pagination:** Full support for Laravel `LengthAwarePaginator`, `Paginator`, and `CursorPaginator`.
- **Extensible via Macros:** Easily extend the class using Laravel's `Macroable` trait.
- **Customizable Keys:** Fully configurable JSON response keys.
- **Note on Eloquent Resources:** When returning a `JsonResource` or `ResourceCollection`, Laravel's native `$wrap` property (usually `"data"`) takes precedence for wrapping the main payload. This is a deliberate design choice to preserve the native behavior of your Eloquent API resources.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require bahadovic/laravel-api-response
```

Optionally, publish the config file to customize keys and messages:

```bash
php artisan vendor:publish --tag="api-response-config"
```

---

## 💡 Usage

### 1. Static One-Liners (Quick & Simple)

```php
use Bahadovic\ApiResponse\Facades\ApiResponse;

// Success Response (200 OK)
return ApiResponse::success($user, 'User profile fetched.');

// Created Response (201 Created)
return ApiResponse::created($user, 'Account created successfully.');

// Error Response (400 Bad Request)
return ApiResponse::error('Unable to process payment.', 400);

// Validation Error (422 Unprocessable Entity)
return ApiResponse::validationError($validator->errors());

// Unauthorized (401) & Forbidden (403)
return ApiResponse::unauthorized();
return ApiResponse::forbidden();

// Not Found (404)
return ApiResponse::notFound('Post not found.');

// No Content (204)
return ApiResponse::noContent();
```

---

### 2. Fluent Chaining Interface

```php
use Bahadovic\ApiResponse\Facades\ApiResponse;

return ApiResponse::make()
    ->data($orders)
    ->message('Orders loaded successfully.')
    ->status(200)
    ->withMeta([
        'execution_time_ms' => 45,
        'server' => 'app-node-01',
    ])
    ->withHeaders([
        'X-API-Version' => 'v2',
    ])
    ->send();
```

---

### 3. Pagination Support (Eloquent & Cursor)

Simply pass any paginator instance directly to `success()`:

```php
// Standard LengthAware Pagination
$users = User::paginate(15);
return ApiResponse::success($users);

// High-Performance Cursor Pagination
$logs = ActivityLog::cursorPaginate(20);
return ApiResponse::success($logs);
```

**Output Example (LengthAware):**
```json
{
    "success": true,
    "message": "Operation completed successfully.",
    "data": [...],
    "meta": {
        "per_page": 15,
        "has_more": true,
        "total": 120,
        "current_page": 1,
        "last_page": 8
    }
}
```

---

### 4. Controller Trait

Add `HasApiResponse` to your Base Controller:

```php
namespace App\Http\Controllers;

use Bahadovic\ApiResponse\Traits\HasApiResponse;

class UserController extends Controller
{
    use HasApiResponse;

    public function index()
    {
        return $this->successResponse(User::all(), 'Users retrieved.');
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }

        return $this->successResponse($user);
    }
}
```

---

## 🧪 Testing

```bash
composer test
```

---
## 🧩 Compatibility

| Package Version | Laravel Version | PHP Version |
| :--- | :--- | :--- |
| **^1.0** | 10.x, 11.x, 12.x | ^8.2 |

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
