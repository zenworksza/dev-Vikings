<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\FranchiseeApplication;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Single choke point for application document files: which disk they live
 * on, how they're encrypted, how they're named (never the user's filename —
 * that's kept only as metadata), and how they're served.
 *
 * Files are encrypted in the app (AES-256-GCM, see config/documents.php)
 * before they reach the disk, so the bucket only ever holds ciphertext.
 * Downloads are decrypted in the app behind an authorization check; there is
 * no public or signed URL to a document.
 *
 * In production a document may only be written to a remote S3 disk with an
 * explicit encryption key — see assertSafeForProduction().
 */
class DocumentStorage
{
    public function disk(): string
    {
        return config('filesystems.documents_disk');
    }

    /**
     * Fail closed: real applicant documents must never land on the server's
     * local disk, or be encrypted with a fallback key, because an env var was
     * missed at deploy time.
     */
    public function assertSafeForProduction(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (config('filesystems.disks.'.$this->disk().'.driver') !== 's3') {
            throw new RuntimeException(
                "Refusing to store documents on disk '{$this->disk()}': production requires an S3 disk (set DOCUMENTS_DISK=documents)."
            );
        }

        if (blank(config('documents.encryption_key'))) {
            throw new RuntimeException('Refusing to store documents: DOCUMENTS_ENCRYPTION_KEY is not set.');
        }
    }

    public function store(FranchiseeApplication $application, UploadedFile $file, string $type): ApplicationDocument
    {
        $this->assertSafeForProduction();

        $path = "applications/{$application->getKey()}/".Str::uuid().'.enc';

        $stored = Storage::disk($this->disk())->put(
            $path,
            $this->encrypter()->encryptString((string) $file->get()),
            'private',
        );

        if (! $stored) {
            throw new RuntimeException('The document could not be stored.');
        }

        return $application->documents()->create([
            'type' => $type,
            'disk' => $this->disk(),
            'path' => $path,
            'encrypted' => true,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function delete(ApplicationDocument $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    /** Decrypts in the app and streams the original file to the caller. */
    public function download(ApplicationDocument $document): StreamedResponse
    {
        $stored = Storage::disk($document->disk)->get($document->path);

        if (! is_string($stored)) {
            throw new RuntimeException('The document file is missing from storage.');
        }

        try {
            $contents = $document->encrypted ? $this->encrypter()->decryptString($stored) : $stored;
        } catch (DecryptException $e) {
            throw new RuntimeException('The document could not be decrypted (wrong key or corrupted file).', 0, $e);
        }

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $document->original_name,
            array_filter(['Content-Type' => $document->mime_type]),
        );
    }

    private function encrypter(): Encrypter
    {
        $key = config('documents.encryption_key') ?: config('app.key');

        if (str_starts_with((string) $key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new Encrypter($key, config('documents.cipher'));
    }
}
