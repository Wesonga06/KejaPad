<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Turnover checklist stages
    |--------------------------------------------------------------------------
    |
    | The coordinated turnover checklist. Stage completion is derived from the
    | underlying records (move-out request, inspections, maintenance tasks,
    | deposit settlement and unit occupancy) rather than being copied into a
    | competing store. "mandatory" marks the stages that must be complete before
    | a unit may be confirmed ready for letting. Optional stages are surfaced
    | when relevant but never block readiness purely because they are pending.
    |
    */
    'stages' => [
        'notice_received' => ['label' => 'Move-out notice received', 'mandatory' => true],
        'reviewed' => ['label' => 'Landlord review of the request', 'mandatory' => true],
        'date_confirmed' => ['label' => 'Move-out date confirmed', 'mandatory' => true],
        'inspection_scheduled' => ['label' => 'Caretaker inspection scheduled', 'mandatory' => true],
        'inspection_completed' => ['label' => 'Inspection completed', 'mandatory' => true],
        'repairs_assessed' => ['label' => 'Repairs assessed and assigned (if needed)', 'mandatory' => false],
        'repairs_verified' => ['label' => 'Repair completion verified', 'mandatory' => false],
        'handover_recorded' => ['label' => 'Keys and handover information recorded', 'mandatory' => false],
        'settlement_reviewed' => ['label' => 'Deposit settlement reviewed', 'mandatory' => false],
        'clearance_completed' => ['label' => 'Landlord clearance completed', 'mandatory' => true],
        'unit_ready' => ['label' => 'Unit marked ready for a new tenant', 'mandatory' => true],
        'new_move_in' => ['label' => 'New tenancy and move-in inspection completed', 'mandatory' => false],
    ],

    // A turnover is flagged as approaching when its proposed move-out date is
    // within this many days and the unit is not yet ready for letting.
    'approaching_days' => (int) env('TURNOVER_APPROACHING_DAYS', 7),
];
