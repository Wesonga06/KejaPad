<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveOut\AssignInspectionRequest;
use App\Http\Requests\MoveOut\ClearMoveOutRequestRequest;
use App\Http\Requests\MoveOut\ReviewMoveOutRequestRequest;
use App\Models\MoveOutRequest;
use App\Models\Property;
use App\Models\User;
use App\Services\MoveOutService;
use Illuminate\Http\Request;

class LandlordMoveOutController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MoveOutRequest::class);

        $base = MoveOutRequest::whereHas('property', function ($q) {
            $q->where('user_id', auth()->id());
        });

        $query = clone $base;

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($propertyId = $request->integer('property_id')) {
            $query->where('property_id', $propertyId);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        $moveOuts = $query->with(['tenant', 'property', 'unit'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'open' => (clone $base)->whereNotIn('status', [
                MoveOutRequest::STATUS_COMPLETED,
                MoveOutRequest::STATUS_REJECTED,
                MoveOutRequest::STATUS_CANCELLED,
            ])->count(),
            'awaiting_review' => (clone $base)->whereIn('status', [
                MoveOutRequest::STATUS_SUBMITTED,
                MoveOutRequest::STATUS_UNDER_REVIEW,
                MoveOutRequest::STATUS_AWAITING_TENANT_INFO,
            ])->count(),
            'inspection' => (clone $base)->whereIn('status', [
                MoveOutRequest::STATUS_APPROVED,
                MoveOutRequest::STATUS_INSPECTION_SCHEDULED,
                MoveOutRequest::STATUS_INSPECTION_COMPLETED,
                MoveOutRequest::STATUS_AWAITING_CLEARANCE,
            ])->count(),
            'completed' => (clone $base)->where('status', MoveOutRequest::STATUS_COMPLETED)->count(),
        ];

        $properties = Property::where('user_id', auth()->id())->orderBy('name')->get();

        return view('landlord.move-outs.index', compact('moveOuts', 'properties', 'stats'));
    }

    public function show(MoveOutRequest $moveOut)
    {
        $this->authorize('view', $moveOut);

        $moveOut->load([
            'tenant',
            'property',
            'unit',
            'reviewedBy',
            'clearedBy',
            'inspections.items',
            'inspections.caretaker',
        ]);

        $deposit = $moveOut->tenant?->securityDeposit;
        $caretakers = $moveOut->property?->caretakers()->orderBy('name')->get() ?? collect();

        return view('landlord.move-outs.show', compact('moveOut', 'deposit', 'caretakers'));
    }

    public function review(ReviewMoveOutRequestRequest $request, MoveOutRequest $moveOut, MoveOutService $service)
    {
        $service->transition($moveOut, $request->status, auth()->user(), [
            'decision_reason' => $request->decision_reason,
            'clarification_message' => $request->clarification_message,
        ]);

        return back()->with('success', 'Notice to Vacate updated to '
            .MoveOutRequest::statusLabel($request->status).'.');
    }

    public function assignInspection(AssignInspectionRequest $request, MoveOutRequest $moveOut, MoveOutService $service)
    {
        $caretaker = User::findOrFail($request->caretaker_id);

        $service->assignInspection($moveOut, $caretaker, $request->scheduled_at, auth()->user());

        return back()->with('success', 'Move-out inspection assigned to '.$caretaker->name.'.');
    }

    public function clear(ClearMoveOutRequestRequest $request, MoveOutRequest $moveOut, MoveOutService $service)
    {
        $service->clear($moveOut, auth()->user(), [
            'clearance_decision' => $request->clearance_decision,
            'actual_move_out_date' => $request->actual_move_out_date,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Tenancy cleared and closed. The unit is now vacant.');
    }
}
