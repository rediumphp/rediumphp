# Redium Framework

A lightweight, modern PHP framework for rapid API development, based on our production-tested patterns

## Features

**Attribute-Based Routing** - Clean, declarative route definitions using PHP 8+ attributes  
**Built-in JWT Authentication** - Token-based auth with permission system  
**Robust ORM** - Full-featured Entity system with relationships, casting, and dirty tracking  
**Query Builder** - Fluent interface for complex database queries  
**Validation System** - 10+ validation rules with database uniqueness checks  
**Caching Layer** - File-based caching with TTL and remember pattern  
**Event System** - Event-driven architecture with priority listeners  
**File Upload Handler** - Comprehensive file management with validation  
**Rate Limiting** - IP-based API protection and throttling  
**Collection Class** - Powerful array manipulation for entities  
**Response Helpers** - Standardized API responses  
**Auto Parameter Injection** - Automatic injection of request data into controller methods  
**Type-Safe** - Full PHP 8.1+ type safety throughout  
**Zero Configuration** - Works out of the box with sensible defaults

## Installation

### Framework Installation

```bash
composer require likore/rediumphp
```

### Quick Start Project

```bash
# Clone or copy the quick-start example
cp -r examples/quick-start my-api
cd my-api

# Install dependencies
composer install

# Setup environment
cp .env.example .env
# Edit .env with your database credentials

# Import database
mysql -u root -p < database.sql

# Start development server
php -S localhost:8000 index.php
```

## Quick Example

### 1. Create a Model

```php
<?php
namespace App\Models;

use Redium\Database\Model;

class Product extends Model
{
    protected string $table = 'products';

    public function findByCategory(string $category): array
    {
        return $this->findAllBy('category', $category);
    }
}
```

### 2. Create a Controller

```php
<?php
namespace App\Controllers;

use App\Models\Product;use Redium\Core\Attributes\Route;use Redium\Core\Controller;

#[Route("/products")]
class ProductController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    #[Route("/list", "GET")]
    public function list(): array
    {
        return $this->productModel->findAll();
    }

    #[Route("/create", "POST", permission: "product.create")]
    public function create(array $data): array
    {
        $this->validate($data, ['name', 'price']);
        $this->productModel->save($data);
        return $this->success("Product created");
    }

    #[Route("/{id}", "GET")]
    public function getProduct(int $id): array
    {
        return $this->productModel->findById($id);
    }
}
```

### 3. Register Controller

```php
<?php
require 'vendor/autoload.php';

use Redium\Core\Application;
use App\Controllers\ProductController;

$app = new Application(__DIR__);
$app->registerController(ProductController::class);
$app->run();
```

That's it! Your API is ready at `http://localhost:8000/v1/products/list`

## Core Concepts

### Attribute-Based Routing

Routes are defined using PHP attributes directly on controller methods:

```php
#[Route("/users")]  // Controller-level prefix
class UserController extends Controller
{
    #[Route("/login", "POST")]  // Public route
    public function login(string $email, string $password): array
    {
        // Handles POST /v1/users/login
    }

    #[Route("/list", "GET", permission: "user.read")]  // Protected route
    public function list(): array
    {
        // Requires valid JWT with "user.read" permission
    }

    #[Route("/{id}", "PUT", permission: "user.update")]
    public function update(int $id, array $data): array
    {
        // Path parameters automatically injected
        // Request body automatically parsed to $data
    }
}
```

### Automatic Parameter Injection

The framework automatically injects parameters from:

-   **Request body** (JSON)
-   **Path parameters** (`{id}`, `{name}`, etc.)
-   **Query parameters** (`?page=1&size=10`)

```php
#[Route("/{id}", "PUT")]
public function update(
    int $id,              // From path: /users/123
    string $name,         // From body: {"name": "John"}
    int $page = 0         // From query: ?page=2 (with default)
): array {
    // All parameters automatically available
}
```

### Authentication & Permissions

Protect routes with the `permission` parameter:

```php
#[Route("/admin", "GET", permission: "admin.access")]
public function adminPanel(): array
{
    // Only users with "admin.access" permission can access
}

#[Route("/public", "GET")]  // No permission = public route
public function publicEndpoint(): array
{
    // Anyone can access
}
```

**Login and get token:**

```bash
curl -X POST http://localhost:8000/v1/users/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

**Use token in requests:**

```bash
curl http://localhost:8000/v1/users/list \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Database Layer

The base `Model` class provides common operations:

```php
class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';  // Default: 'id'
}

// CRUD Operations
$user = new User();

// Create
$user->save(['name' => 'John', 'email' => 'john@example.com']);

// Read
$all = $user->findAll();                    // Get all with pagination
$one = $user->findById(1);                  // Find by ID
$byEmail = $user->findBy('email', 'john@example.com');  // Find by field

// Update
$user->update(['id' => 1, 'name' => 'Jane']);
$user->updateBy('identifier123', 'status', 'ACTIVE');

// Delete
$user->delete(1);

// Count
$total = $user->count();
$active = $user->countBy('status', 'ACTIVE');
```

**View Projections** (select specific fields):

```php
// Only select specific fields
$users = $user->findAll('id, name, email', $size = 10, $page = 0);
```

### Configuration

Environment variables are automatically loaded from `.env`:

```php
use Redium\Utils\Config;

// Access config with dot notation
$dbHost = Config::get('database.host');
$appName = Config::get('app.name', 'Default Name');

// Check environment
if (Config::isDev()) {
    // Development-only code
}
```

### Helper Functions

Common utilities are available globally:

```php
// Request/Response
$data = getBody();                          // Parse JSON request body
$json = writeBody($data);                   // Encode to JSON
throwError(404, "Not Found", "User not found");  // Send error response

// Security
$hash = password_encrypt($password);        // Encrypt password
$safe = getTextInput($userInput);           // Sanitize input

// Utilities
$id = generateUniqueIdentifier();           // Generate UUID
$serial = generateRandomSerialNumber(8);    // Random alphanumeric
$ip = getIpAddress();                       // Get client IP

// HTTP Requests
$data = callGet('https://api.example.com/data');
$result = callPost('https://api.example.com/create', ['name' => 'John']);
```

## API Endpoints (Quick Start Example)

| Method | Endpoint                 | Permission  | Description              |
| ------ | ------------------------ | ----------- | ------------------------ |
| POST   | `/v1/users/login`        | none        | User login               |
| GET    | `/v1/users/list`         | user.read   | Get all users            |
| GET    | `/v1/users/{identifier}` | user.read   | Get single user          |
| POST   | `/v1/users/create`       | all         | Create user (admin only) |
| PUT    | `/v1/users/{identifier}` | user.update | Update user              |
| DELETE | `/v1/users/{identifier}` | all         | Delete user (admin only) |

## Testing the Example

### 1. Login as Admin

```bash
curl -X POST http://localhost:8000/v1/users/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

Response:

```json
{
	"user": {
		"identifier": "admin001",
		"first_name": "Admin",
		"email": "admin@example.com",
		"role": "ADMIN"
	},
	"token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 2. Get Users (with token)

```bash
curl http://localhost:8000/v1/users/list \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Create User (admin only)

```bash
curl -X POST http://localhost:8000/v1/users/create \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "password": "password123",
    "role": "USER"
  }'
```

## Project Structure

```
your-api/
├── src/
│   ├── Controllers/     # API controllers with routes
│   ├── Models/          # Database models
│   └── Services/        # Business logic
├── .env                 # Environment configuration
├── index.php            # Application entry point
├── composer.json
└── database.sql         # Database schema
```

## Environment Variables

```env
# Application
ENV=dev                          # dev or production
APP_NAME="My API"
APP_URL=http://localhost:8000

# Database
DB_HOST=localhost
DB_NAME=my_database
DB_USER=root
DB_PASSWORD=

# JWT
JWT_SECRET=your-secret-key
JWT_EXPIRATION_HOURS=8

# CORS
ALLOWED_URLS=http://localhost:3000,http://localhost:8000
```

## Best Practices

### 1. Use Services for Business Logic

```php
// Don't put logic in controllers
#[Route("/create", "POST")]
public function create(array $data): array
{
    if (!$data['email']) throw new Exception("Email required");
    $user = new User();
    $user->save($data);
    // ... more logic
}

// Use services
#[Route("/create", "POST")]
public function create(array $data): array
{
    return $this->userService->createUser($data);
}
```

### 2. Validate Input

```php
public function create(array $data): array
{
    $this->validate($data, ['name', 'email', 'password']);
    // ... proceed with validated data
}
```

### 3. Use View Projections

```php
// Don't expose sensitive data
$users = $this->userModel->findAll(); // Includes passwords!

// Select only needed fields
$users = $this->userModel->findAll('id, name, email, role');
```

### 4. Handle Errors Gracefully

```php
public function getUser(string $id): array
{
    $user = $this->userModel->findById($id);

    if (!$user) {
        throw new Exception("User not found");
    }

    return $user;
}
```

## Requirements

-   PHP 8.1 or higher
-   MySQL/MariaDB
-   Composer
-   PDO extension
-   JSON extension

## License

MIT License - feel free to use in your projects!

## Credits

Built by Likore, extracted from production-tested patterns in real-world applications.
