<?php

use Albaroody\Staging\Models\StagingEntry;
use Albaroody\Staging\Services\StagingManager;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Facades\Schema;
use Albaroody\Staging\Http\Controllers\Controller;
use Illuminate\Http\Request;

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