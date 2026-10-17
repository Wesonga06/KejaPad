<?php

namespace App\Http\Controllers;

use App\Models\Turnover;
use App\Services\TurnoverService;

class CaretakerTurnoverController extends Controller
{
    public function __construct(protected TurnoverService $turnovers) {}

    public function index()
    {
        $this->authorize('viewAny', Turnover::class);

        $user = auth()->user();
        $propertyIds = $user->caretakerProperties()->pluck('properties.id');

        $turnovers = Turnover::where(function ($query) use ($propertyIds, $user) {
            $query->whereIn('property_id', $propertyIds)
                ->orWhereHas('tasks', fn ($q) => $q->where('assigned_to', $user->id));
        })
            ->with(['property', 'unit', 'outgoingTenant'])
            ->withCount(['tasks as assigned_task_count' => fn ($q) => $q->where('assigned_to', $user->id)])
            ->latest('last_activity_at')
            ->paginate(15);

        return view('caretaker.turnovers.index', compact('turnovers'));
    }

    public function show(Turnover $turnover)
    {
        $this->authorize('view', $turnover);

        $turnover->load([
            'property',
            'unit',
            'outgoingTenant',
            'moveOutRequest.inspection.items',
            'tasks.assignedTo',
            'tasks.verifiedBy',
        ]);

        $checklist = $this->turnovers->checklist($turnover);
        $nextAction = $this->turnovers->nextAction($turnover);

        return view('caretaker.turnovers.show', compact('turnover', 'checklist', 'nextAction'));
    }
}
