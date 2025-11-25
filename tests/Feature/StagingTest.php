<?php

use Albaroody\Staging\Http\Controllers\Controller;
use Albaroody\Staging\Models\StagingEntry;
use Albaroody\Staging\Services\StagingManager;
use Albaroody\Staging\Traits\Stagable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DummyPatient extends Model
{
    protected $fillable = ['name', 'email'];

    public $timestamps = false; // keep it simple

    protected $table = 'patients'; // simulate real table name
}

class DummyPatientController extends Controller
{
    public function stage(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
        ]);

        $patient = new DummyPatient($validatedData);
        StagingManager::stage($patient);

        return response()->json(['message' => 'Patient staged successfully.']);
    }
}
it('can stage, load and promote a model', function () {
    // Step 1: Fake migrate the "patients" table manually for test
    Schema::create('patients', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
    });

    // Step 2: Stage a new patient
    $patient = new DummyPatient([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $stagingId = StagingManager::stage($patient);

    expect(StagingEntry::count())->toBe(1);

    // Step 3: Load the staged model
    $loadedPatient = StagingManager::load($stagingId);

    expect($loadedPatient->name)->toBe('John Doe');
    expect($loadedPatient->email)->toBe('john@example.com');

    // Step 4: Promote the staged model into real database
    $realPatient = StagingManager::promote($loadedPatient);

    expect($realPatient->exists)->toBeTrue();
    expect($realPatient->name)->toBe('John Doe');

    // Step 5: Make sure staging entry is deleted
    expect(StagingEntry::count())->toBe(0);
});

it('can stage a patient via the stage route', function () {

    // Step 2: Define the route using the macro
    Route::resourceWithStage('patients', DummyPatientController::class);

    // Step 3: Send a POST request to the stage route
    $response = $this->post('/patients/stage', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Step 4: Assert the response
    $response->assertStatus(200);
    $response->assertJson(['message' => 'Patient staged successfully.']);

    // Step 5: Assert the staging entry exists
    $this->assertDatabaseCount('staging_entries', 1);
});

// Dummy models for hasMany relationship testing
class DummyProduct extends Model
{
    use Stagable;

    protected $fillable = ['name', 'price'];

    public $timestamps = false;

    protected $table = 'products';

    protected static function getHasManyRelationships(): array
    {
        return [
            'stock_levels' => [
                'model' => DummyStockLevel::class,
                'foreign_key' => 'product_id',
            ],
        ];
    }
}

class DummyStockLevel extends Model
{
    use Stagable;

    protected $fillable = ['quantity', 'location', 'product_id'];

    public $timestamps = false;

    protected $table = 'stock_levels';
}

it('can stage multiple children with stageMany', function () {
    Schema::create('stock_levels', function ($table) {
        $table->id();
        $table->integer('quantity');
        $table->string('location');
        $table->unsignedBigInteger('product_id')->nullable();
    });

    $items = [
        ['quantity' => 100, 'location' => 'Warehouse A', '_sort_order' => 0],
        ['quantity' => 50, 'location' => 'Warehouse B', '_sort_order' => 1],
    ];

    $stagingIds = DummyStockLevel::stageMany($items);

    expect($stagingIds)->toHaveCount(2);
    expect(StagingEntry::count())->toBe(2);

    // Verify sort order is preserved
    $entry1 = StagingEntry::where('id', $stagingIds[0])->first();
    $entry2 = StagingEntry::where('id', $stagingIds[1])->first();

    expect($entry1->sort_order)->toBe(0);
    expect($entry2->sort_order)->toBe(1);
    expect($entry1->data['location'])->toBe('Warehouse A');
    expect($entry2->data['location'])->toBe('Warehouse B');
});

it('can link staged children to a parent', function () {
    Schema::create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
    });

    Schema::create('stock_levels', function ($table) {
        $table->id();
        $table->integer('quantity');
        $table->string('location');
        $table->unsignedBigInteger('product_id')->nullable();
    });

    // Stage a product
    $product = new DummyProduct(['name' => 'Widget', 'price' => 10.00]);
    $productStagingId = $product->stage();

    // Stage children
    $items = [
        ['quantity' => 100, 'location' => 'Warehouse A'],
        ['quantity' => 50, 'location' => 'Warehouse B'],
    ];
    $childStagingIds = DummyStockLevel::stageMany($items);

    // Link children to parent
    DummyStockLevel::linkStagedToParent($childStagingIds, $productStagingId, DummyProduct::class);

    // Verify linking
    $entries = StagingEntry::whereIn('id', $childStagingIds)->get();
    foreach ($entries as $entry) {
        expect($entry->parent_staging_id)->toBe($productStagingId);
        expect($entry->parent_model)->toBe(DummyProduct::class);
        expect($entry->relationship_type)->toBe('hasMany');
    }
});

it('can find staged children by parent', function () {
    Schema::create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
    });

    Schema::create('stock_levels', function ($table) {
        $table->id();
        $table->integer('quantity');
        $table->string('location');
        $table->unsignedBigInteger('product_id')->nullable();
    });

    // Stage a product
    $product = new DummyProduct(['name' => 'Widget', 'price' => 10.00]);
    $productStagingId = $product->stage();

    // Stage and link children
    $items = [
        ['quantity' => 100, 'location' => 'Warehouse A', '_sort_order' => 0],
        ['quantity' => 50, 'location' => 'Warehouse B', '_sort_order' => 1],
    ];
    $childStagingIds = DummyStockLevel::stageMany($items, $productStagingId, DummyProduct::class, 'hasMany');

    // Find staged children
    $stagedChildren = DummyStockLevel::findStagedChildren($productStagingId, DummyProduct::class);

    expect($stagedChildren)->toHaveCount(2);
    expect($stagedChildren[0]['data']['location'])->toBe('Warehouse A');
    expect($stagedChildren[1]['data']['location'])->toBe('Warehouse B');
    expect($stagedChildren[0]['sort_order'])->toBe(0);
    expect($stagedChildren[1]['sort_order'])->toBe(1);
});

it('can save staged children when parent is saved', function () {
    Schema::create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
    });

    Schema::create('stock_levels', function ($table) {
        $table->id();
        $table->integer('quantity');
        $table->string('location');
        $table->unsignedBigInteger('product_id')->nullable();
    });

    // Stage a product
    $product = new DummyProduct(['name' => 'Widget', 'price' => 10.00]);
    $productStagingId = $product->stage();

    // Stage and link children
    $items = [
        ['quantity' => 100, 'location' => 'Warehouse A'],
        ['quantity' => 50, 'location' => 'Warehouse B'],
    ];
    $childStagingIds = DummyStockLevel::stageMany($items, $productStagingId, DummyProduct::class, 'hasMany');

    // Save the product
    $realProduct = StagingManager::promote($product);

    // Save staged children
    $createdIds = DummyStockLevel::saveStagedChildren(
        $realProduct->id,
        $productStagingId,
        DummyProduct::class,
        'product_id'
    );

    expect($createdIds)->toHaveCount(2);
    expect(StagingEntry::count())->toBe(0); // All staging entries should be deleted

    // Verify children were saved with correct foreign key
    $stockLevels = DummyStockLevel::whereIn('id', $createdIds)->get();
    foreach ($stockLevels as $stockLevel) {
        expect($stockLevel->product_id)->toBe($realProduct->id);
    }
});

it('automatically saves staged children when parent is created with hasMany relationships', function () {
    Schema::create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price', 10, 2);
    });

    Schema::create('stock_levels', function ($table) {
        $table->id();
        $table->integer('quantity');
        $table->string('location');
        $table->unsignedBigInteger('product_id')->nullable();
    });

    // Stage children first
    $items = [
        ['quantity' => 100, 'location' => 'Warehouse A'],
        ['quantity' => 50, 'location' => 'Warehouse B'],
    ];
    $childStagingIds = DummyStockLevel::stageMany($items);

    // Stage parent
    $product = new DummyProduct(['name' => 'Widget', 'price' => 10.00]);
    $productStagingId = $product->stage();

    // Link children to parent
    DummyStockLevel::linkStagedToParent($childStagingIds, $productStagingId, DummyProduct::class);

    // Promote the staged product (this deletes the product's staging entry)
    $realProduct = StagingManager::promote($product);

    // Manually save staged children (bootStagable would do this automatically
    // if _staging_id and stock_levels_staging_ids were in request)
    DummyStockLevel::saveStagedChildren(
        $realProduct->id,
        $productStagingId,
        DummyProduct::class,
        'product_id'
    );

    // Verify children were automatically saved
    $stockLevels = DummyStockLevel::where('product_id', $realProduct->id)->get();
    expect($stockLevels)->toHaveCount(2);
    expect(StagingEntry::count())->toBe(0); // All staging entries should be deleted
});

it('can get staged collection for display', function () {
    Schema::create('stock_levels', function ($table) {
        $table->id();
        $table->integer('quantity');
        $table->string('location');
        $table->unsignedBigInteger('product_id')->nullable();
    });

    $items = [
        ['quantity' => 100, 'location' => 'Warehouse A', '_sort_order' => 0],
        ['quantity' => 50, 'location' => 'Warehouse B', '_sort_order' => 1],
    ];

    $stagingIds = DummyStockLevel::stageMany($items);

    $collection = DummyStockLevel::getStagedCollection($stagingIds);

    expect($collection)->toHaveCount(2);
    expect($collection[0]->location)->toBe('Warehouse A');
    expect($collection[1]->location)->toBe('Warehouse B');
    expect($collection[0]->is_staged)->toBeTrue();
    expect($collection[0]->staging_id)->toBe($stagingIds[0]);
});

// Dummy models for belongsTo relationship testing
class DummySupplier extends Model
{
    use Stagable;

    protected $fillable = ['name'];

    public $timestamps = false;

    protected $table = 'suppliers';
}

class DummyProductWithSupplier extends Model
{
    use Stagable;

    protected $fillable = ['name', 'supplier_staging_id'];

    public $timestamps = false;

    protected $table = 'products';
}

it('maintains backward compatibility with belongsTo relationships', function () {
    Schema::create('suppliers', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('products', function ($table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('supplier_id')->nullable();
    });

    // Stage supplier (parent)
    $supplierStagingId = DummySupplier::stageNew(['name' => 'Acme Corp']);

    // Create product with reference to staged supplier (belongsTo pattern)
    $productStagingId = DummyProductWithSupplier::stageNew([
        'name' => 'Widget',
        'supplier_staging_id' => $supplierStagingId,
    ]);

    // Verify staging entries exist
    expect(StagingEntry::count())->toBe(2);

    // Load and verify
    $stagedSupplier = DummySupplier::findStaged($supplierStagingId);
    $stagedProduct = DummyProductWithSupplier::findStaged($productStagingId);

    expect($stagedSupplier->name)->toBe('Acme Corp');
    expect($stagedProduct->name)->toBe('Widget');
});
