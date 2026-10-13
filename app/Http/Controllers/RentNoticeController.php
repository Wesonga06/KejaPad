<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rent\StoreRentNoticeRequest;
use App\Models\RentAudit;
use App\Models\RentNotice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RentNoticeController extends Controller
{
    // Record a reminder or formal notice against a tenant (Feature 5, stage 4).
    public function store(StoreRentNoticeRequest $request, Tenant $tenant)
    {
        $notice = RentNotice::create([
            'tenant_id' => $tenant->id,
            'rent_record_id' => $request->input('rent_record_id'),
            'type' => $request->type,
            'stage' => $this->stageForType($request->type),
            'issued_at' => $request->issued_at,
            'recipient' => $request->recipient,
            'delivery_status' => $request->input('delivery_status', 'sent'),
            'deadline' => $request->deadline,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        RentAudit::record(RentAudit::ACTION_NOTICE_ISSUED, $notice, [
            'tenant_id' => $tenant->id,
            'type' => $notice->type,
            'delivery_status' => $notice->delivery_status,
            'issued_at' => $notice->issued_at->toDateString(),
        ]);

        return back()->with('success', 'Notice recorded successfully.');
    }

    // Update delivery status / deadline of an existing notice.
    public function update(Request $request, RentNotice $notice)
    {
        $this->authorizeNotice($notice);

        $data = $request->validate([
            'delivery_status' => ['required', Rule::in(['draft', 'sent', 'delivered', 'failed', 'acknowledged'])],
            'deadline' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $old = $notice->only(['delivery_status', 'deadline']);

        $notice->update($data);

        RentAudit::record(RentAudit::ACTION_NOTICE_ISSUED, $notice, [
            'delivery_status' => $notice->delivery_status,
            'deadline' => $notice->deadline?->toDateString(),
        ], $old);

        return back()->with('success', 'Notice updated.');
    }

    private function stageForType(string $type): int
    {
        $schedule = array_keys(config('rent.reminder_schedule', []));
        $index = array_search($type, $schedule, true);

        if ($index === false) {
            return 4; // formal notices / demand letters are stage 4+
        }

        return $index + 1;
    }

    private function authorizeNotice(RentNotice $notice): void
    {
        abort_unless(
            $notice->tenant?->property?->user_id === auth()->id(),
            403,
        );
    }
}
