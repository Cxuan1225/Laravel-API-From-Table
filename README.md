# Laravel API From Table

Generate Laravel API classes from existing database tables.

Laravel API From Table is a database-first code generator for Laravel projects. It reads an existing table and generates the API layer around it: model, form requests, DTOs, actions, resource, and controller.

---

## Why This Package Exists

Laravel already provides commands such as:

```bash
php artisan make:model Customer -crR
```

That command is useful for generating empty skeleton files.

However, it does not inspect your existing database table columns, nullable fields, defaults, indexes, or column types.

This package reads your database table schema and generates more useful Laravel code based on the actual database structure.

---

## Generated Files

Running the command for a `customers` table can generate:

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

Generated API flow:

```txt
FormRequest
→ DTO
→ Action
→ Eloquent Model
→ JsonResource
→ API Controller
```

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

- Generate Eloquent models from existing database tables
- Generate `$fillable` from table columns
- Generate model casts from database column types
- Generate Store and Update FormRequest rules
- Generate Store and Update DTO classes from validated data
- Generate Store and Update Action classes
- Generate API resources
- Generate API resource controllers with `index`, `store`, `show`, `update`, and `destroy`
- Support `--dry-run` preview
- Support JSON dry-run plans for tooling
- Support `--force` overwrite
- Optional API route, smoke test, and relationship generation
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

The generated controller is API-oriented and wires requests, DTOs, actions, and resources together:

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

## Dry Run

Preview the generated files without writing them:

```bash
php artisan api:from-table customers --dry-run
```

For machine-readable previews:

```bash
php artisan api:from-table customers --dry-run --json
```

The JSON output lists planned files, warnings, skipped files, route targets, and test targets.

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

## Generate Routes

Append an API resource route to the configurable routes path, which defaults to `routes/web.php`:

```bash
php artisan api:from-table customers --routes
```

Append the route to `routes/api.php`:

```bash
php artisan api:from-table customers --api-routes
```

---

## Generate Relationships

Relationship generation is opt-in. When enabled, single-column foreign keys can generate `belongsTo`, inverse `hasMany`, and `whenLoaded` resource fields:

```bash
php artisan api:from-table orders --relationships
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
        'email' => ['nullable', 'string', 'email', 'max:255'],
        'credit_limit' => ['nullable', 'numeric'],
        'is_active' => ['nullable', 'boolean'],
    ];
}
```

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
| `--routes` | Append a `Route::apiResource(...)` entry to the configured routes path |
| `--api-routes` | Append a `Route::apiResource(...)` entry to `routes/api.php` |
| `--relationships` | Generate opt-in Eloquent relationships and `whenLoaded` resource fields from foreign keys |
| `--tests` | Generate a Pest endpoint smoke test |
| `--json` | Emit JSON output when combined with `--dry-run` |
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

## Current Status

The first release focuses on generating a practical Laravel API layer from one existing database table:

- Model with `$fillable`, casts, and non-conventional table name support
- Automatic `$hidden` output for sensitive fields and hashed password casts
- Store and Update FormRequest classes
- Store and Update DTO classes
- Store and Update Action classes
- API Resource class
- API Controller with `index`, `store`, `show`, `update`, and `destroy`
- Optional `routes/web.php` or `routes/api.php` route snippet and stronger Pest smoke test generation
- Optional foreign-key relationship generation with resource `whenLoaded` output
- `--dry-run`, `--force`, and file-type-only generation options
- `--dry-run --json` plans for tooling
- Publishable config and stubs

---

## Roadmap

### Schema Intelligence

- Foreign key detection
- Check constraint parsing across more database drivers

### API Ergonomics

- Optional policy generation
- Smarter validation rules from column names such as `url`

### Customization

- More granular config for generated namespaces and class names
- Optional timestamps in resources
- Optional typed DTO properties

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

Laravel API From Table is open-sourced software licensed under the [MIT license](LICENSE).
