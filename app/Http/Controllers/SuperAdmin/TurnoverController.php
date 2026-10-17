<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Turnover;
use App\Services\TurnoverService;

class TurnoverController extends Controller
{
    public function __construct(protected TurnoverService $turnovers) {}

    public function index()
    {
        $this->authorize('viewAny', Turnover::class);

        $turnovers = Turnover::with(['property.landlord', 'unit', 'outgoingTenant', 'nextTenant'])
            ->latest('last_activity_at')
            ->latest('id')
            ->paginate(20);

        return view('super_admin.turnovers.index', compact('turnovers'));
    }

    public function show(Turnover $turnover)
    {
        $this->authorize('view', $turnover);

        $turnover->load([
            'property.landlord',
            'unit',
            'outgoingTenant',
            'nextTenant',
            'readinessConfirmedBy',
            'moveOutRequest.inspection',
            'moveOutRequest.settlement',
            'tasks.assignedTo',
            'events.user',
        ]);

        $checklist = $this->turnovers->checklist($turnover);
        $nextAction = $this->turnovers->nextAction($turnover);

        return view('super_admin.turnovers.show', compact('turnover', 'checklist', 'nextAction'));
    }
}
