
# Laravel Staging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/albaroody/staging.svg?style=flat-square)](https://packagist.org/packages/albaroody/staging)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/albaroody/staging/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/albaroody/staging/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/albaroody/staging/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/albaroody/staging/actions?query=workflow%3AFix+PHP+code+style+issues+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/albaroody/staging.svg?style=flat-square)](https://packagist.org/packages/albaroody/staging)

---

Laravel Staging allows you to **stage (draft)** Eloquent models and their **nested relationships** into a clean, separate system before committing them permanently to your main database.

- Stage parent models like `Patient`, `Post`, `Order`, etc.
- Stage related models like `Sales`, `Items`, `Comments`, etc.
- Hydrate full Eloquent models from staged data (not just arrays)
- Promote staged data to production tables cleanly
- Keep your main database structure untouched — no intrusive columns added!

Perfect for multi-step forms, draft publishing systems, and modular deferred saving workflows.

---

## Installation

You can install the package via Composer:

```bash
composer require albaroody/staging
```
You can publish and run the staging migration with
```bash
php artisan vendor:publish --tag="staging-migrations"
php artisan migrate
```
You can publish the config file with:
```bash
php artisan vendor:publish --tag="staging-config"
```

## Usage
1. Add the Stagable trait to your model:
```php
use Albaroody\\Staging\\Traits\\Stagable;

class Patient extends Model
{
    use Stagable;
}
```
2. Stage a model:
```php
// Option 1: Stage an existing model instance
$patient = new Patient([
    'name' => 'John Doe',
]);

$stagingId = $patient->stage();

// Option 2: Stage a new model using static method
$stagingId = Patient::stageNew([
    'name' => 'John Doe',
]);
```

3. Load a staged model:
```php
$stagedPatient = Patient::findStaged($stagingId);

// Now you can use it like a normal model
echo $stagedPatient->name;
```

4. Promote a staged model to the database:
```php
$realPatient = $stagedPatient->promoteFromStaging();

// The staging entry is automatically deleted after promotion
```

## HasMany Relationships

The library supports staging `hasMany` relationships, allowing you to create multiple child records before saving the parent.

### Basic Usage

1. **Stage multiple children at once:**

```php
$stockLevels = [
    ['quantity' => 100, 'location' => 'Warehouse A', '_sort_order' => 0],
    ['quantity' => 50, 'location' => 'Warehouse B', '_sort_order' => 1],
];

$stagingIds = StockLevel::stageMany($stockLevels);
```

2. **Link staged children to a parent:**

```php
// Stage the parent first
$product = new Product(['name' => 'Widget', 'price' => 10.00]);
$productStagingId = $product->stage();

// Link staged children to parent
StockLevel::linkStagedToParent($stagingIds, $productStagingId, Product::class);
```

3. **Find staged children by parent:**

```php
$stagedChildren = StockLevel::findStagedChildren($productStagingId, Product::class);

foreach ($stagedChildren as $child) {
    echo $child['data']['location']; // 'Warehouse A', 'Warehouse B'
}
```

4. **Save staged children when parent is saved:**

```php
// Save the parent
$realProduct = $product->promoteFromStaging();

// Save all staged children with the parent's ID
$createdIds = StockLevel::saveStagedChildren(
    $realProduct->id,
    $productStagingId,
    Product::class,
    'product_id' // foreign key column name
);
```

### Automatic Child Saving

You can configure models to automatically save staged children when the parent is created via HTTP request:

1. **Define hasMany relationships in your model:**

```php
use Albaroody\Staging\Traits\Stagable;

class Product extends Model
{
    use Stagable;

    protected static function getHasManyRelationships(): array
    {
        return [
            'stock_levels' => [
                'model' => StockLevel::class,
                'foreign_key' => 'product_id',
            ],
        ];
    }
}
```

2. **Create parent via HTTP request with staging IDs in the request:**

```php
// In your controller's store method
public function store(Request $request)
{
    // Stage children first (if coming from form)
    $stockLevelIds = StockLevel::stageMany([
        ['quantity' => 100, 'location' => 'Warehouse A'],
        ['quantity' => 50, 'location' => 'Warehouse B'],
    ]);

    // Stage parent
    $product = new Product($request->only(['name', 'price']));
    $productStagingId = $product->stage();

    // Link children
    StockLevel::linkStagedToParent($stockLevelIds, $productStagingId, Product::class);

    // Create product - staging IDs must be in the request for automatic saving
    $product = Product::create($request->only(['name', 'price']) + [
        '_staging_id' => $productStagingId,
        'stock_levels_staging_ids' => $stockLevelIds,
    ]);

    // Children are automatically saved with product_id set!
    return $product;
}
```

**Note:** The automatic child saving works by checking the request for `_staging_id` and `{relationship_name}_staging_ids` keys. These keys must be present in the request when creating the parent model.

### Get Staged Collection for Display

Get a collection of staged objects for display in forms or tables:

```php
$stagingIds = StockLevel::stageMany([
    ['quantity' => 100, 'location' => 'Warehouse A'],
    ['quantity' => 50, 'location' => 'Warehouse B'],
]);

$collection = StockLevel::getStagedCollection($stagingIds);

// Use like Eloquent models
foreach ($collection as $item) {
    echo $item->location; // 'Warehouse A', 'Warehouse B'
    echo $item->is_staged; // true
}
```

### Staging Endpoints (Optional)

You can use the included Route macro to easily add staging endpoints to your controllers:

```php
// In routes/web.php or routes/api.php
use Illuminate\Support\Facades\Route;

Route::resourceWithStage('products', ProductController::class);
```

This will create:
- `POST /products/stage` - Stage a product (in addition to standard resource routes)
- All standard resource routes (`GET /products`, `POST /products`, etc.)

Your controller should implement a `stage` method:

```php
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function stage(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
        ]);

        $product = new Product($validatedData);
        $stagingId = $product->stage();

        return response()->json([
            'message' => 'Product staged successfully.',
            'staging_id' => $stagingId,
        ]);
    }
}
```

**Note:** Your `Product` model must use the `Stagable` trait for the `stage()` method to be available.

### Storing References to Staged Models

You can store references to staged models in your attributes (useful for belongsTo relationships):

```php
// Stage supplier first
$supplierStagingId = Supplier::stageNew(['name' => 'Acme Corp']);

// Stage product with reference to staged supplier
$productStagingId = Product::stageNew([
    'name' => 'Widget',
    'supplier_staging_id' => $supplierStagingId, // Store reference
]);

// Later, when promoting, you'll need to handle the relationship manually:
$stagedProduct = Product::findStaged($productStagingId);
$stagedSupplier = Supplier::findStaged($stagedProduct->supplier_staging_id);

$realSupplier = $stagedSupplier->promoteFromStaging();
$stagedProduct->supplier_id = $realSupplier->id; // Set actual foreign key
$realProduct = $stagedProduct->promoteFromStaging();
```

## Features

✅ Stage any Eloquent model as a draft  
✅ Stage multiple children at once with `stageMany()`  
✅ Link staged children to staged parents  
✅ Automatic saving of hasMany children when parent is created  
✅ Preserve sort order for staged items  
✅ Get staged collections for display (model-like objects)  
✅ Clean promotion to production tables  
✅ No intrusive columns in your main tables  

Enjoy easy staging without cluttering your main table with extra columns!

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Albaroody](https://github.com/Albaroody)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
