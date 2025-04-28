<?php

use Albaroody\Staging\Models\StagingEntry;
use Albaroody\Staging\Services\StagingManager;
use Illuminate\Database\Eloquent\Model;

class DummyPatient extends Model
{
    protected $fillable = ['name', 'email'];
    public $timestamps = false; // keep it simple
    protected $table = 'patients'; // simulate real table name
}

it('can stage, load and promote a model', function () {
    // Step 1: Fake migrate the "patients" table manually for test
    \Illuminate\Support\Facades\Schema::create('patients', function ($table) {
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
