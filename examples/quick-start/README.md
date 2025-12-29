# Redium Quick Start Example

A complete working example demonstrating the Redium framework with user authentication and CRUD operations.

## Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and set your database credentials:

```env
DB_HOST=localhost
DB_NAME=redium_demo
DB_USER=root
DB_PASSWORD=your_password
```

### 3. Create Database

```bash
mysql -u root -p < database.sql
```

This creates the database and inserts two demo users:

-   **Admin**: `admin@example.com` / `admin123`
-   **User**: `john@example.com` / `user123`

### 4. Start Server

```bash
php -S localhost:8000 index.php
```

## API Endpoints

### Public Endpoints

#### Login

```bash
POST /v1/users/login

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
		"last_name": "User",
		"email": "admin@example.com",
		"role": "ADMIN",
		"status": "ACTIVE"
	},
	"token": "eyJ0eXAiOiJKV1Qi..."
}
```

### Protected Endpoints (Require Authentication)

Save the token from login and use it in the `Authorization` header:

#### List Users

```bash
GET /v1/users/list?page=0&size=10

curl http://localhost:8000/v1/users/list \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get Single User

```bash
GET /v1/users/{identifier}

curl http://localhost:8000/v1/users/admin001 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Create User (Admin Only)

```bash
POST /v1/users/create

curl -X POST http://localhost:8000/v1/users/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "password": "password123",
    "role": "USER"
  }'
```

#### Update User

```bash
PUT /v1/users/{identifier}

curl -X PUT http://localhost:8000/v1/users/user001 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John Updated",
    "last_name": "Doe Updated"
  }'
```

#### Delete User (Admin Only)

```bash
DELETE /v1/users/{identifier}

curl -X DELETE http://localhost:8000/v1/users/user001 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Project Structure

```
quick-start/
├── src/
│   ├── Controllers/
│   │   └── UserController.php    # API routes and handlers
│   ├── Models/
│   │   └── User.php               # Database model
│   └── Services/
│       └── UserService.php        # Business logic
├── .env.example                   # Environment template
├── composer.json                  # Dependencies
├── database.sql                   # Database schema
├── index.php                      # Application entry point
└── README.md                      # This file
```

## How It Works

### 1. Entry Point (`index.php`)

```php
$app = new Application(__DIR__);
$app->registerController(UserController::class);
$app->run();
```

### 2. Controller (`UserController.php`)

Uses attributes to define routes:

```php
#[Route("/users")]
class UserController extends Controller
{
    #[Route("/login", "POST")]
    public function login(string $email, string $password): array
    {
        return $this->userService->login($email, $password);
    }

    #[Route("/list", "GET", permission: "user.read")]
    public function list(): array
    {
        return $this->userService->getAllUsers();
    }
}
```

### 3. Service (`UserService.php`)

Contains business logic:

```php
public function login(string $email, string $password): array
{
    $user = $this->userModel->findByEmailAndPassword($email, $password);
    // Generate JWT token
    // Return user + token
}
```

### 4. Model (`User.php`)

Handles database operations:

```php
class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }
}
```

## Permission System

The example implements a simple role-based permission system:

-   **ADMIN** role: Has `all` permission (full access)
-   **USER** role: Has `user.read` and `user.update` permissions

Routes are protected using the `permission` parameter:

```php
#[Route("/create", "POST", permission: "all")]  // Admin only
#[Route("/list", "GET", permission: "user.read")]  // User or Admin
#[Route("/login", "POST")]  // Public (no permission)
```

## Extending the Example

### Add a New Entity

1. **Create Model** (`src/Models/Product.php`):

```php
class Product extends Model
{
    protected string $table = 'products';
}
```

2. **Create Service** (`src/Services/ProductService.php`):

```php
class ProductService
{
    public function getAllProducts(): array
    {
        return $this->productModel->findAll();
    }
}
```

3. **Create Controller** (`src/Controllers/ProductController.php`):

```php
#[Route("/products")]
class ProductController extends Controller
{
    #[Route("/list", "GET")]
    public function list(): array
    {
        return $this->productService->getAllProducts();
    }
}
```

4. **Register Controller** (`index.php`):

```php
$app->registerController(ProductController::class);
```

## Troubleshooting

### Database Connection Error

-   Check your `.env` file has correct database credentials
-   Ensure MySQL is running
-   Verify the database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### 401 Unauthorized

-   Make sure you're including the token: `-H "Authorization: Bearer YOUR_TOKEN"`
-   Token might be expired (default: 8 hours)
-   Try logging in again to get a fresh token

### 403 Forbidden

-   Your user doesn't have the required permission
-   Admin endpoints require `all` permission (ADMIN role only)

## Next Steps

-   Add more entities (products, orders, etc.)
-   Implement email verification
-   Add file upload support
-   Create API documentation with Swagger
-   Add unit tests
-   Deploy to production

## Support

For issues or questions about the Redium framework, please refer to the main README.md in the framework root.
