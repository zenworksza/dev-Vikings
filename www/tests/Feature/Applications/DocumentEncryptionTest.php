<?php

use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Services\DocumentStorage;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->application = FranchiseeApplication::create(['user_id' => User::factory()->create()->id]);
    $this->plaintext = "SECRET-ID-DOCUMENT 8503045800087 \x00\x01\x02 binary";
});

function fakeUpload(string $contents, string $name = 'id.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $contents);
}

test('what reaches the disk is ciphertext, not the document', function () {
    $document = app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext), 'identity');

    $onDisk = Storage::disk('local')->get($document->path);

    expect($document->encrypted)->toBeTrue()
        ->and($onDisk)->not->toContain('SECRET-ID-DOCUMENT')
        ->and($onDisk)->not->toContain('8503045800087')
        ->and($onDisk)->not->toBe($this->plaintext);
});

test('an admin download returns the original bytes', function () {
    $document = app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext, 'passport.pdf'), 'identity');

    $admin = User::factory()->create()->assignRole('platform_admin');
    $response = $this->actingAs($admin)->get(route('admin.application-documents.download', $document));

    $response->assertOk();
    expect($response->streamedContent())->toBe($this->plaintext)
        ->and($response->headers->get('content-disposition'))->toContain('passport.pdf');
});

test('a tampered file fails to decrypt instead of returning garbage', function () {
    $document = app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext), 'identity');

    $cipher = Storage::disk('local')->get($document->path);
    Storage::disk('local')->put($document->path, substr($cipher, 0, -6).'AAAAAA');

    expect(fn () => app(DocumentStorage::class)->download($document))
        ->toThrow(RuntimeException::class, 'could not be decrypted');
});

test('a document cannot be read with a different key', function () {
    $document = app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext), 'identity');

    config(['documents.encryption_key' => 'base64:'.base64_encode(random_bytes(32))]);

    expect(fn () => app(DocumentStorage::class)->download($document))
        ->toThrow(RuntimeException::class, 'could not be decrypted');
});

test('a dedicated key is used when configured, and differs from APP_KEY', function () {
    $key = 'base64:'.base64_encode(random_bytes(32));
    config(['documents.encryption_key' => $key]);

    $document = app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext), 'identity');

    // Readable with the dedicated key...
    expect(app(DocumentStorage::class)->download($document))->toBeInstanceOf(StreamedResponse::class);

    // ...but not with the app key alone.
    config(['documents.encryption_key' => null]);
    expect(fn () => app(DocumentStorage::class)->download($document))->toThrow(RuntimeException::class);
});

test('production refuses to store a document on local disk', function () {
    $this->app['env'] = 'production';
    config(['filesystems.documents_disk' => 'local', 'documents.encryption_key' => 'base64:'.base64_encode(random_bytes(32))]);

    expect(fn () => app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext), 'identity'))
        ->toThrow(RuntimeException::class, 'requires an S3 disk');

    expect($this->application->documents()->count())->toBe(0);
});

test('production refuses to store a document without a dedicated encryption key', function () {
    $this->app['env'] = 'production';
    config(['filesystems.documents_disk' => 'documents', 'documents.encryption_key' => null]);

    expect(fn () => app(DocumentStorage::class)->store($this->application, fakeUpload($this->plaintext), 'identity'))
        ->toThrow(RuntimeException::class, 'DOCUMENTS_ENCRYPTION_KEY');
});

test('production accepts an S3 disk with an encryption key', function () {
    $this->app['env'] = 'production';
    config(['filesystems.documents_disk' => 'documents', 'documents.encryption_key' => 'base64:'.base64_encode(random_bytes(32))]);

    app(DocumentStorage::class)->assertSafeForProduction();

    expect(config('filesystems.disks.documents.driver'))->toBe('s3')
        ->and(config('filesystems.disks.documents.visibility'))->toBe('private');
});
