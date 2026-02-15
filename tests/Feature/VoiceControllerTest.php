<?php

use App\Models\Document;
use Illuminate\Support\Facades\DB;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('can view voice assistant page', function () {
    $response = test()->get('/');

    $response->assertStatus(200);
});

test('can import markdown files', function () {
    // Clean up any existing documents
    Document::query()->delete();

    // Run the import command
    test()->artisan('markdown:import')
        ->assertExitCode(0);

    // Check that documents were created
    expect(Document::count())->toBe(3);

    // Check that specific documents exist
    expect(DB::table('documents')->where([
        'title' => 'Welcome to the Knowledge Base',
        'file_path' => 'welcome.md',
    ])->exists())->toBeTrue();
});

test('can search documents using text query', function () {
    // Create a test document
    Document::create([
        'title' => 'Test Document',
        'content' => 'This is a test about Laravel and voice assistants.',
        'file_path' => 'test.md',
        'embedding' => null,
    ]);

    // Search for similar documents
    $results = Document::searchSimilar('Laravel voice assistant');

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Test Document');
});
