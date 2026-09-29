<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Services\DocumentStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentController extends Controller
{
    /** Admin download of an applicant's uploaded document. */
    public function __invoke(Request $request, ApplicationDocument $document, DocumentStorage $storage): StreamedResponse
    {
        abort_unless($request->user()?->hasRole('platform_admin'), 403);

        return $storage->download($document);
    }
}
