<?php

use App\Models\FranchiseeApplication;
use App\Models\User;
use App\Services\DocumentStorage;
use Illuminate\Http\UploadedFile;

/** Loads config/filesystems.php as the app would with the given env. */
function filesystemsConfigWith(array $env): array
{
    foreach ($env as $key => $value) {
        $value === null ? putenv($key) : putenv("$key=$value");
    }

    try {
        return require config_path('filesystems.php');
    } finally {
        foreach (array_keys($env) as $key) {
            putenv($key);
        }
    }
}

test('the documents disk writes every object with SSE-KMS when a key is configured', function () {
    $config = filesystemsConfigWith(['AWS_KMS_KEY_ID' => 'arn:aws:kms:af-south-1:111122223333:key/abc']);

    expect($config['disks']['documents'])
        ->driver->toBe('s3')
        ->visibility->toBe('private')
        ->throw->toBeTrue()
        ->options->toBe([
            'ServerSideEncryption' => 'aws:kms',
            'SSEKMSKeyId' => 'arn:aws:kms:af-south-1:111122223333:key/abc',
        ]);
});

test('without a KMS key the documents disk is not considered encrypted', function () {
    config(['filesystems.disks.documents' => filesystemsConfigWith(['AWS_KMS_KEY_ID' => null])['disks']['documents']]);

    expect(app(DocumentStorage::class)->isEncrypted('documents'))->toBeFalse()
        ->and(app(DocumentStorage::class)->isEncrypted('local'))->toBeFalse();

    config(['filesystems.disks.documents' => filesystemsConfigWith(['AWS_KMS_KEY_ID' => 'key-1'])['disks']['documents']]);

    expect(app(DocumentStorage::class)->isEncrypted('documents'))->toBeTrue();
});

test('production refuses to store a document anywhere but encrypted S3', function () {
    $this->app['env'] = 'production';
    config(['filesystems.documents_disk' => 'local']);

    $application = FranchiseeApplication::create(['user_id' => User::factory()->create()->id]);

    expect(fn () => app(DocumentStorage::class)->store(
        $application,
        UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'),
        'identity',
    ))->toThrow(RuntimeException::class, 'requires an S3 disk with SSE-KMS');

    expect($application->documents()->count())->toBe(0);
});

test('production accepts an SSE-KMS S3 disk', function () {
    $this->app['env'] = 'production';
    config([
        'filesystems.documents_disk' => 'documents',
        'filesystems.disks.documents' => filesystemsConfigWith(['AWS_KMS_KEY_ID' => 'key-1'])['disks']['documents'],
    ]);

    app(DocumentStorage::class)->assertEncryptedInProduction();

    expect(true)->toBeTrue(); // no exception
});
