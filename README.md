# Laravel API From Table

Generate Laravel API classes from existing database tables.

This package is designed for **database-first Laravel projects**, legacy systems, admin panels, and business systems where the database schema already exists before the Laravel code structure is created.

---

## Why This Package Exists

Laravel already provides commands such as:

```bash
php artisan make:model Customer -crR
```

That command is useful for generating empty skeleton files.

However, it does not inspect your existing database table columns, nullable fields, indexes, or column types.

This package reads your database table schema and generates more useful Laravel code based on the actual database structure.

---

## Core Idea

```txt
Database Table
→ Schema Reader
→ TableSchema DTO
→ Inferrers
→ Generators
→ Stub Renderer
→ File Writer
```

The package does not simply replace words inside a template.  
The real value is the conversion pipeline:

```txt
columns / types / nullable / defaults
→ fillable / casts / validation rules
→ generated Laravel files
```

---

## Features

- Generate Eloquent Model from an existing database table
- Generate `$fillable` from table columns
- Generate model casts from database column types
- Generate Store FormRequest rules
- Generate Update FormRequest rules
- Generate Store and Update DTO classes
- Generate Store and Update Action classes
- Generate API Resource classes
- Generate API resource controllers
- Support `--dry-run` preview
- Support `--force` overwrite
- Publishable config
- Publishable stubs

---

## Requirements

- PHP 8.2+
- Laravel 12 or 13
- Composer

---

## Installation

Install the package via Composer:

```bash
composer require cxuan1225/laravel-api-from-table --dev
```

This package is intended to be used during development, so installing it with `--dev` is recommended.

---

## Usage

Generate model, requests, DTOs, actions, resource, and API controller from a database table:

```bash
php artisan api:from-table customers
```

This will generate files such as:

```txt
app/Models/Customer.php
app/Http/Requests/StoreCustomerRequest.php
app/Http/Requests/UpdateCustomerRequest.php
app/Data/StoreCustomerData.php
app/Data/UpdateCustomerData.php
app/Actions/Customers/StoreCustomerAction.php
app/Actions/Customers/UpdateCustomerAction.php
app/Http/Resources/CustomerResource.php
app/Http/Controllers/CustomerController.php
```

---

## Dry Run

Preview the generated files without writing them:

```bash
php artisan api:from-table customers --dry-run
```

---

## Force Overwrite

Overwrite existing generated files:

```bash
php artisan api:from-table customers --force
```

---

## Generate Only Model

```bash
php artisan api:from-table customers --model
```

---

## Generate Only Requests

```bash
php artisan api:from-table customers --requests
```

---

## Generate Only DTOs

```bash
php artisan api:from-table customers --dto
```

---

## Generate Only Actions

```bash
php artisan api:from-table customers --actions
```

---

## Generate Only API Resource

```bash
php artisan api:from-table customers --resource
```

---

## Generate Only API Controller

```bash
php artisan api:from-table customers --controller
```

---

## Example

Given this database table:

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    credit_limit DECIMAL(12,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

The package can generate a model like this:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Customer extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
```

It can also generate FormRequest rules:

```php
public function rules(): array
{
    return [
        'company_id' => ['required', 'integer'],
        'name' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'string', 'max:255'],
        'credit_limit' => ['nullable', 'numeric'],
        'is_active' => ['nullable', 'boolean'],
    ];
}
```

It also wires generated API classes together:

```php
public function store(StoreCustomerRequest $request): CustomerResource
{
    $customer = $this->storeCustomerAction->handle(
        StoreCustomerData::fromRequest($request),
    );

    return new CustomerResource($customer);
}
```

---

## Command Options

| Option | Description |
|---|---|
| `--dry-run` | Preview generated code without writing files |
| `--force` | Overwrite existing files |
| `--model` | Generate only the model |
| `--requests` | Generate only FormRequest files |
| `--dto` | Generate only DTO files |
| `--actions` | Generate only Action files |
| `--resource` | Generate only the API Resource file |
| `--controller` | Generate only the API Controller file |
| `--all` | Generate all supported files, ignoring disabled defaults |

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=api-from-table-config
```

The config file will be published to:

```txt
config/api-from-table.php
```

---

## Custom Stubs

Publish the stubs:

```bash
php artisan vendor:publish --tag=api-from-table-stubs
```

The stubs will be published to:

```txt
stubs/vendor/api-from-table
```

You may customize these stub files to match your own Laravel project style.

Available stubs:

```txt
model.stub
request.store.stub
request.update.stub
data.store.stub
data.update.stub
action.store.stub
action.update.stub
resource.stub
controller.api.stub
```

---

## Roadmap

### v0.1.0

- Generate Model
- Generate StoreRequest
- Generate UpdateRequest
- Generate StoreData
- Generate UpdateData
- Generate StoreAction
- Generate UpdateAction
- Generate API Resource
- Generate API Controller
- Generate fillable
- Generate casts
- Generate basic validation rules
- Support dry run
- Support force overwrite

### v0.2.0

- Foreign key detection
- BelongsTo relationship generation
- Exists validation rule generation
- Unique index detection

### v0.3.0

- Service generator
- Request-aware policy hints
- Route example generation

### v0.4.0

- Controller generator
- API preset
- Inertia preset

---

## Testing

Run the test suite:

```bash
composer test
```

Or run Pest directly:

```bash
vendor/bin/pest
```

---

## License

The MIT License.
