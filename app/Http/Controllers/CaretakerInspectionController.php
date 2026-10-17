<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveOut\SubmitInspectionRequest;
use App\Models\MoveOutInspection;
use App\Services\MoveOutService;
use Illuminate\Http\Request;

class CaretakerInspectionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MoveOutInspection::class);

        $base = MoveOutInspection::where('caretaker_id', auth()->id());

        $status = $request->string('status')->toString();
        $query = clone $base;
        if ($status) {
            $query->where('status', $status);
        }

        $inspections = $query->with(['moveOutRequest.tenant', 'moveOutRequest.unit', 'moveOutRequest.property'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pending = (clone $base)->whereIn('status', [
            MoveOutInspection::STATUS_ASSIGNED,
            MoveOutInspection::STATUS_IN_PROGRESS,
        ])->count();
        $submitted = (clone $base)->where('status', MoveOutInspection::STATUS_SUBMITTED)->count();

        return view('caretaker.inspections.index', compact('inspections', 'pending', 'submitted'));
    }

    public function show(MoveOutInspection $inspection)
    {
        $this->authorize('view', $inspection);

        $inspection->load(['moveOutRequest.tenant', 'moveOutRequest.unit', 'moveOutRequest.property', 'items']);

        return view('caretaker.inspections.show', compact('inspection'));
    }

    public function submit(SubmitInspectionRequest $request, MoveOutInspection $inspection, MoveOutService $service)
    {
        $photos = [];
        foreach ($request->file('photos', []) as $file) {
            $photos[] = $file->store('inspections', 'public');
        }

        $service->submitInspection($inspection, [
            'keys_returned' => $request->boolean('keys_returned'),
            'keys_notes' => $request->keys_notes,
            'electricity_meter' => $request->electricity_meter,
            'water_meter' => $request->water_meter,
            'cleanliness' => $request->cleanliness,
            'general_notes' => $request->general_notes,
            'photos' => $photos,
            'recommendation' => $request->recommendation,
            'items' => $request->input('items', []),
        ], auth()->user());

        return redirect()->route('caretaker.inspections.show', $inspection)
            ->with('success', 'Inspection submitted successfully.');
    }
}
