# Redium Framework - Improvements & Enhancements

## New Features Added

### 1. ✅ Validation System (`src/Validation/`)

**Validator Class** - Comprehensive validation with multiple rules:

```php
use Redium\Validation\Validator;

// In your controller
$validator = Validator::make($data, [
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8',
    'age' => 'numeric|min:18',
    'role' => 'in:USER,ADMIN'
]);

if ($validator->fails()) {
    return Response::validationError($validator->errors());
}
```

**Available Rules:**

-   `required` - Field must be present and not empty
-   `email` - Must be valid email
-   `min:n` - Minimum length
-   `max:n` - Maximum length
-   `numeric` - Must be numeric
-   `alpha` - Only letters
-   `alphanumeric` - Letters and numbers only
-   `url` - Valid URL
-   `in:val1,val2` - Must be one of specified values
-   `unique:table,column` - Must be unique in database

---

### 2. ✅ Response Helper (`src/Http/Response.php`)

**Standardized API Responses:**

```php
use Redium\Http\Response;

// Success responses
return Response::success($data, 'Operation successful');
return Response::created($user, 'User created');
return Response::paginated($items, $total, $page, $size);

// Error responses
return Response::error('Something went wrong', 400);
return Response::notFound('User not found');
return Response::unauthorized();
return Response::forbidden();
return Response::validationError($errors);
```

**Benefits:**

-   Consistent response format across all endpoints
-   Automatic HTTP status code setting
-   Built-in pagination support
-   Clear success/error distinction

---

### 3. ✅ Query Builder (`src/Database/QueryBuilder.php`)

**Fluent Interface for Complex Queries:**

```php
use Redium\Database\QueryBuilder;

// Select with conditions
$users = QueryBuilder::table('users')
    ->select('id', 'name', 'email')
    ->where('status', 'ACTIVE')
    ->where('age', '>=', 18)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Search
$results = QueryBuilder::table('products')
    ->whereLike('name', '%phone%')
    ->whereIn('category', ['electronics', 'gadgets'])
    ->get();

// Count
$total = QueryBuilder::table('users')
    ->where('role', 'ADMIN')
    ->count();

// Insert
QueryBuilder::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);

// Update
QueryBuilder::table('users')
    ->where('id', 1)
    ->update(['status' => 'ACTIVE']);

// Delete
QueryBuilder::table('users')
    ->where('status', 'DELETED')
    ->delete();
```

---

### 4. ✅ Middleware Support (`src/Http/Middleware.php`)

**Base Class for Custom Middleware:**

```php
namespace App\Middleware;

use Redium\Http\Middleware;

class LoggingMiddleware extends Middleware
{
    public function process($request, $response, $next)
    {
        // Before request
        error_log("Request: " . $request->getUri());

        // Process request
        $response = $next($request, $response);

        // After request
        error_log("Response: " . $response->getStatusCode());

        return $response;
    }
}
```

---

## Updated Controller Example

Here's how to use the new features in your controllers:

```php
<?php

namespace App\Controllers;

use Redium\Core\Controller;
use Redium\Attributes\Route;
use Redium\Validation\Validator;
use Redium\Http\Response;
use Redium\Database\QueryBuilder;
use App\Services\UserService;

#[Route("/users")]
class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    #[Route("/create", "POST", permission: "user.create")]
    public function create(array $data): array
    {
        // Validate input
        $validator = Validator::make($data, [
            'first_name' => 'required|min:2|max:50',
            'last_name' => 'required|min:2|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'in:USER,ADMIN'
        ]);

        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }

        // Create user
        $user = $this->userService->createUser($data);

        // Return standardized response
        return Response::created($user, 'User created successfully');
    }

    #[Route("/search", "GET", permission: "user.read")]
    public function search(string $query, int $page = 0, int $size = 10): array
    {
        // Use Query Builder for complex search
        $users = QueryBuilder::table('users')
            ->select('id', 'first_name', 'last_name', 'email', 'role')
            ->whereLike('first_name', "%{$query}%")
            ->where('status', 'ACTIVE')
            ->orderBy('first_name', 'ASC')
            ->limit($size)
            ->offset($page * $size)
            ->get();

        $total = QueryBuilder::table('users')
            ->whereLike('first_name', "%{$query}%")
            ->where('status', 'ACTIVE')
            ->count();

        return Response::paginated($users, $total, $page, $size);
    }

    #[Route("/{id}", "GET", permission: "user.read")]
    public function getUser(int $id): array
    {
        $user = QueryBuilder::table('users')
            ->where('id', $id)
            ->where('status', '!=', 'DELETED')
            ->first();

        if (!$user) {
            return Response::notFound('User not found');
        }

        unset($user['password']);
        return Response::success($user);
    }
}
```

---

## Additional Improvements to Consider

### 1. **Caching Layer**

```php
// Future enhancement
Cache::remember('users.all', 3600, function() {
    return User::findAll();
});
```

### 2. **Event System**

```php
// Future enhancement
Event::listen('user.created', function($user) {
    Mail::send($user, 'welcome');
});
```

### 3. **File Upload Handler**

```php
// Future enhancement
$file = FileUpload::store($request->file('avatar'), 'avatars');
```

### 4. **Rate Limiting**

```php
// Future enhancement
#[Route("/api/endpoint", "GET", rateLimit: "60 per minute")]
```

### 5. **API Documentation Generator**

```php
// Future enhancement - Auto-generate OpenAPI/Swagger docs from Route attributes
```

---

## Summary of Improvements

| Feature            | Status     | Benefit                                  |
| ------------------ | ---------- | ---------------------------------------- |
| Validation System  | ✅ Added   | Input validation with 10+ rules          |
| Response Helper    | ✅ Added   | Standardized API responses               |
| Query Builder      | ✅ Added   | Fluent interface for complex queries     |
| Middleware Support | ✅ Added   | Extensibility for cross-cutting concerns |
| Caching            | 📋 Planned | Performance optimization                 |
| Events             | 📋 Planned | Decoupled application logic              |
| File Uploads       | 📋 Planned | Easy file handling                       |
| Rate Limiting      | 📋 Planned | API protection                           |

The framework is now more robust, developer-friendly, and production-ready! 🚀
