<?php

use App\Enums\DocumentStatus;
use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Services\DocumentStorage;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->application = FranchiseeApplication::create(['user_id' => User::factory()->create()->id]);
});

test('a document is stored privately under a generated name', function () {
    $document = app(DocumentStorage::class)->store(
        $this->application,
        UploadedFile::fake()->create('my passport.pdf', 120, 'application/pdf'),
        'identity',
    );

    expect($document->original_name)->toBe('my passport.pdf')
        ->and($document->type)->toBe('identity')
        ->and($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->disk)->toBe('local')
        ->and($document->path)->toStartWith("applications/{$this->application->id}/")
        ->and($document->path)->not->toContain('passport');

    Storage::disk('local')->assertExists($document->path);
});

test('deleting a document removes the file and the row', function () {
    $storage = app(DocumentStorage::class);
    $document = $storage->store($this->application, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'identity');
    $path = $document->path;

    $storage->delete($document);

    Storage::disk('local')->assertMissing($path);
    expect($this->application->documents()->count())->toBe(0);
});

test('only platform admins can download a document', function () {
    $document = app(DocumentStorage::class)->store(
        $this->application,
        UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        'identity',
    );
    $url = route('admin.application-documents.download', $document);

    $this->get($url)->assertRedirect('/login');

    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();

    $admin = User::factory()->create()->assignRole('platform_admin');
    $this->actingAs($admin)->get($url)->assertOk();
});
