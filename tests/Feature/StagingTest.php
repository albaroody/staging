<?php

use Albaroody\Staging\Models\StagingEntry;
use Albaroody\Staging\Services\StagingManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
class DummyPatient extends Model
{
    protected $fillable = ['name', 'email'];
    public $timestamps = false; // keep it simple
    protected $table = 'patients'; // simulate real table name
}

it('confirms the staging table exists', function () {
    expect(Schema::hasTable('staging_entries'))->toBeTrue();
});
it('can stage, load and promote a model', function () {
    
    // Step 1: create fake migration for the patients table
    Schema::create('patients', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
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

it('can stage and promote a model with children', function () {
    // Step 1: Define a simple parent-child relationship within the same table for simplicity
    Schema::create('patients', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('parent_id')->nullable(); // For simplicity, we'll use the same table for parent-child
    });

    class NestedPatient extends Model
    {
        protected $fillable = ['id', 'name', 'email', 'parent_id'];
        public $timestamps = false;
        protected $table = 'patients';
        
    }

    // Step 2: Create a parent and two children
    $parent = new NestedPatient([ 'name' => 'Parent', 'email' => 'parent@example.com']);
    $stagingParentId = StagingManager::stage($parent);

    $child1 = new NestedPatient(['name' => 'Child 1', 'email' => 'child1@example.com']);
    $stagingChild1Id = StagingManager::stage($child1, $stagingParentId);

    $grandchild1 = new NestedPatient(['name' => 'Grandchild 1', 'email' => 'grandchild1@example.com']);
    $stagingGrandchild1Id = StagingManager::stage($grandchild1, $stagingChild1Id);

    // Step 3: Verify staging entries
    expect(StagingEntry::count())->toBe(3);

    // Step 4: Promote all (parent and children)
    $loadedParent = StagingManager::load($stagingParentId);
    StagingManager::promoteAll($loadedParent);

    // Step 5: Verify promotion and cleanup

    expect(StagingEntry::count())->toBe(0);
    expect(NestedPatient::count())->toBe(3);

    // Step 6: Verify data integrity after promotion
    $promotedParent = NestedPatient::where('name', 'Parent')->first();

    expect($promotedParent)->not->toBeNull();

    $promotedChild1 = NestedPatient::where('name', 'Child 1')->first();
    expect($promotedChild1)->not->toBeNull();

    $promotedGrandchild1 = NestedPatient::where('name', 'Grandchild 1')->first();
    expect($promotedGrandchild1)->not->toBeNull();
});

