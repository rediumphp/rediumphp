# Redium Framework - Standalone Setup Guide

## 📦 Extracting as Standalone Project

This guide will help you extract the Redium framework to a new directory and set it up as a completely standalone
project.

---

## Method 1: Copy the Entire Framework

### Step 1: Copy Framework Directory

```bash
# Copy the entire framework to your desired location
cp -r c:\DELTA\UPI\stock_api\delta-php-framework c:\your\desired\location\redium-framework

# Or on Windows Command Prompt:
xcopy "c:\DELTA\UPI\stock_api\delta-php-framework" "c:\your\desired\location\redium-framework" /E /I
```

### Step 2: Install Dependencies

```bash
cd c:\your\desired\location\redium-framework
composer install
```

### Step 3: Setup Example Project

```bash
cd examples\quick-start
composer install
copy .env.example .env
```

### Step 4: Configure Database

Edit `.env` file:

```env
DB_HOST=localhost
DB_NAME=redium_demo
DB_USER=root
DB_PASSWORD=your_password
```

### Step 5: Create Database

```bash
mysql -u root -p < database.sql
```

### Step 6: Run the Example

```bash
php -S localhost:8000 index.php
```

Test it:

```bash
curl -X POST http://localhost:8000/v1/users/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"admin@example.com\",\"password\":\"admin123\"}"
```

---

## Method 2: Create New Project Using Framework

### Step 1: Create Project Structure

```bash
mkdir my-api-project
cd my-api-project
```

### Step 2: Create composer.json

```json
{
	"name": "yourname/my-api",
	"description": "My API built with Redium",
	"type": "project",
	"require": {
		"php": "^8.1",
		"slim/slim": "^4.14",
		"slim/psr7": "^1.7",
		"lcobucci/jwt": "^5.5",
		"vlucas/phpdotenv": "^5.6",
		"ramsey/uuid": "^4.7"
	},
	"autoload": {
		"psr-4": {
			"App\\": "src/",
			"Redium\\": "framework/src/"
		},
		"files": ["framework/src/Utils/Helpers.php"]
	}
}
```

### Step 3: Copy Framework Source

```bash
# Create framework directory
mkdir framework
mkdir framework\src

# Copy framework source files
cp -r c:\DELTA\UPI\stock_api\delta-php-framework\src framework\
```

### Step 4: Create Your Application Structure

```bash
mkdir src
mkdir src\Controllers
mkdir src\Entities
mkdir src\Services
```

### Step 5: Create index.php

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Redium\Core\Application;
use App\Controllers\YourController;

$app = new Application(__DIR__);
$app->registerController(YourController::class);
$app->run();
```

### Step 6: Install and Run

```bash
composer install
php -S localhost:8000 index.php
```

---

## Method 3: Use as Composer Package (Local)

### Step 1: Keep Framework Separate

Keep the framework at: `c:\DELTA\UPI\stock_api\delta-php-framework`

### Step 2: Create New Project

```bash
mkdir my-new-api
cd my-new-api
```

### Step 3: Create composer.json with Local Repository

```json
{
	"name": "yourname/my-api",
	"require": {
		"php": "^8.1",
		"likore/rediumphp": "*"
	},
	"repositories": [
		{
			"type": "path",
			"url": "../delta-php-framework"
		}
	],
	"autoload": {
		"psr-4": {
			"App\\": "src/"
		}
	}
}
```

### Step 4: Install

```bash
composer install
```

This will create a symlink to your framework!

---

## What's Included in Standalone Package

### Framework Files (40+ files)

```
delta-php-framework/
├── src/                          # Framework source code
│   ├── Attributes/               # Route attributes
│   ├── Auth/                     # JWT authentication
│   ├── Cache/                    # Caching system
│   ├── Core/                     # Application core
│   ├── Database/                 # Database layer + ORM
│   ├── Events/                   # Event system
│   ├── Http/                     # HTTP utilities
│   ├── ORM/                      # ORM entities
│   ├── Storage/                  # File upload
│   ├── Utils/                    # Utilities
│   └── Validation/               # Validation
├── examples/                     # Example projects
├── composer.json                 # Framework dependencies
├── README.md                     # Main documentation
├── FEATURES.md                   # Feature guide
├── IMPROVEMENTS.md               # Improvement history
├── FINAL_SUMMARY.md              # Complete summary
└── WALKTHROUGH.md                # Implementation walkthrough
```

### Documentation Files

-   ✅ `README.md` - Quick start guide
-   ✅ `FEATURES.md` - Complete feature documentation
-   ✅ `IMPROVEMENTS.md` - Improvement history
-   ✅ `FINAL_SUMMARY.md` - Complete implementation summary
-   ✅ `WALKTHROUGH.md` - Detailed walkthrough
-   ✅ `examples/quick-start/README.md` - Example project guide

---

## Verifying Standalone Setup

### 1. Check Framework Files

```bash
# Verify all source files exist
ls framework/src/Core/Application.php
ls framework/src/ORM/Entity.php
ls framework/src/Cache/Cache.php
```

### 2. Test Autoloading

Create `test.php`:

```php
<?php
require 'vendor/autoload.php';

use Redium\Cache\Cache;
use Redium\Events\EventDispatcher;

echo "Framework loaded successfully!\n";
```

Run:

```bash
php test.php
```

### 3. Test Example Project

```bash
cd examples/quick-start
php -S localhost:8000 index.php
```

Visit: http://localhost:8000/v1/users/list

---

## Customizing for Your Project

### 1. Update composer.json

Change package name, description, authors:

```json
{
	"name": "yourcompany/your-api",
	"description": "Your API description",
	"authors": [
		{
			"name": "Your Name",
			"email": "your@email.com"
		}
	]
}
```

### 2. Update Environment Variables

Edit `.env`:

```env
APP_NAME="Your API Name"
APP_URL=https://your-domain.com
```

### 3. Customize Database Schema

Modify `database.sql` to add your tables.

### 4. Add Your Entities

Create entities in `src/Entities/`:

```php
<?php
namespace App\Entities;

use Redium\ORM\Entity;

class Product extends Entity
{
    protected static string $table = 'products';
    protected static array $fillable = ['name', 'price', 'description'];
}
```

---

## Production Deployment

### 1. Security Checklist

-   ✅ Change `JWT_SECRET` to a strong random string
-   ✅ Set `ENV=production` in `.env`
-   ✅ Configure proper CORS origins
-   ✅ Enable HTTPS
-   ✅ Set proper file permissions (755 for directories, 644 for files)
-   ✅ Disable error display in production

### 2. Optimize Composer

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Web Server

**Apache (.htaccess):**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

**Nginx:**

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Troubleshooting

### Issue: "Class not found"

**Solution:** Run `composer dump-autoload`

### Issue: "Database connection failed"

**Solution:** Check `.env` database credentials

### Issue: "Permission denied" on cache/uploads

**Solution:**

```bash
chmod -R 755 storage
chmod -R 755 cache
```

### Issue: "JWT token invalid"

**Solution:** Ensure `JWT_SECRET` is set in `.env`

---

## Next Steps

1. ✅ Extract framework to standalone directory
2. ✅ Install dependencies
3. ✅ Configure environment
4. ✅ Create database
5. ✅ Test example project
6. ✅ Start building your API!

---

## Support & Documentation

-   **Main README**: Quick start and basic usage
-   **FEATURES.md**: Complete feature guide with examples
-   **FINAL_SUMMARY.md**: Implementation summary
-   **WALKTHROUGH.md**: Detailed walkthrough

---

## Framework is Ready! 🚀

The Redium framework is now completely standalone and ready to be extracted to any location. All dependencies are
properly configured, and the framework can be used independently.

**Happy coding!** 🎉
