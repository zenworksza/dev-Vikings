<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\FranchiseeApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Single choke point for application document files: which disk they live
 * on, how they're named (never the user's filename — that's kept only as
 * metadata), and how they're served. The disk is private; downloads always
 * go through an authorized route, never a public URL.
 *
 * In production a document may only be written to an S3 disk configured for
 * SSE-KMS — see assertEncryptedInProduction().
 */
class DocumentStorage
{
    public function disk(): string
    {
        return config('filesystems.documents_disk');
    }

    /** True for an S3 disk whose uploads are written with SSE-KMS. */
    public function isEncrypted(string $disk): bool
    {
        $config = config("filesystems.disks.{$disk}");

        return ($config['driver'] ?? null) === 's3'
            && ($config['options']['ServerSideEncryption'] ?? null) === 'aws:kms'
            && filled($config['options']['SSEKMSKeyId'] ?? null);
    }

    /**
     * Fail closed: real applicant documents must never land on local disk or
     * an unencrypted bucket because an env var was missed at deploy time.
     */
    public function assertEncryptedInProduction(): void
    {
        if (app()->isProduction() && ! $this->isEncrypted($this->disk())) {
            throw new RuntimeException(
                "Refusing to store documents on disk '{$this->disk()}': production requires an S3 disk with SSE-KMS "
                .'(set DOCUMENTS_DISK=documents and AWS_KMS_KEY_ID).'
            );
        }
    }

    public function store(FranchiseeApplication $application, UploadedFile $file, string $type): ApplicationDocument
    {
        $this->assertEncryptedInProduction();

        $extension = $file->guessExtension() ?: 'bin';
        $path = $file->storeAs(
            "applications/{$application->getKey()}",
            Str::uuid().'.'.$extension,
            $this->disk(),
        );

        if ($path === false) {
            throw new RuntimeException('The document could not be stored.');
        }

        return $application->documents()->create([
            'type' => $type,
            'disk' => $this->disk(),
            'path' => $path,
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

    /**
     * S3: a short-lived signed URL (the bytes never pass through the app).
     * Otherwise (local dev): stream through the app.
     */
    public function download(ApplicationDocument $document): RedirectResponse|StreamedResponse
    {
        $disk = Storage::disk($document->disk);

        if (config("filesystems.disks.{$document->disk}.driver") === 's3') {
            return redirect()->away($disk->temporaryUrl(
                $document->path,
                now()->addMinutes(5),
                ['ResponseContentDisposition' => 'attachment; filename="'.addslashes($document->original_name).'"'],
            ));
        }

        return $disk->download($document->path, $document->original_name);
    }
}
