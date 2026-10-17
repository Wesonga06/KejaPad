<?php

namespace App\Http\Controllers;

use App\Models\UnitDocument;
use App\Models\UnitInspection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PassportFileController extends Controller
{
    // Stream a private unit document after an authorization check.
    public function download(UnitDocument $document)
    {
        $this->authorize('view', $document);

        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name ?? $document->title);
    }

    // Stream a private inspection photograph by index.
    public function photo(UnitInspection $inspection, int $index): StreamedResponse
    {
        $this->authorize('view', $inspection);

        $photos = array_values($inspection->photos ?? []);

        abort_unless(isset($photos[$index]), 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($photos[$index]), 404);

        return $disk->response($photos[$index]);
    }
}
