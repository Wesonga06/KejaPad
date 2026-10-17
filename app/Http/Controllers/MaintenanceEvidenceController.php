<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequestEvidence;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceEvidenceController extends Controller
{
    // Stream a private task file after an authorization check, so the file can
    // never be reached by guessing a public URL.
    public function show(MaintenanceRequestEvidence $evidence): StreamedResponse
    {
        $this->authorize('viewEvidence', $evidence->maintenanceRequest);

        $disk = Storage::disk($evidence->disk);

        abort_unless($disk->exists($evidence->path), 404);

        return $disk->response($evidence->path, $evidence->original_name ?: 'evidence');
    }
}
