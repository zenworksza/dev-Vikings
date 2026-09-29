<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\FranchiseeApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Single choke point for application document files: which disk they live
 * on, how they're named (never the user's filename — that's kept only as
 * metadata), and how they're served. The disk is private; downloads always
 * go through an authorized route, never a public URL.
 */
class DocumentStorage
{
    public function disk(): string
    {
        return config('filesystems.documents_disk');
    }

    public function store(FranchiseeApplication $application, UploadedFile $file, string $type): ApplicationDocument
    {
        $extension = $file->guessExtension() ?: 'bin';
        $path = $file->storeAs(
            "applications/{$application->getKey()}",
            Str::uuid().'.'.$extension,
            $this->disk(),
        );

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

    public function download(ApplicationDocument $document): StreamedResponse
    {
        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }
}
