<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KejaPadController extends Controller
{
    public function dashboard(): View
    {
        return view('welcome', $this->baseData());
    }

    public function repairs(): View
    {
        $data = $this->baseData();
        $ticket = DB::table('maintenance_tickets')
            ->join('units', 'units.id', '=', 'maintenance_tickets.unit_id')
            ->select('maintenance_tickets.*', 'units.label as unit_label')
            ->latest('maintenance_tickets.created_at')
            ->first();

        $updates = $ticket
            ? DB::table('maintenance_updates')->where('maintenance_ticket_id', $ticket->id)->orderBy('position')->get()
            : collect();

        return view('repairs', [...$data, 'ticket' => $ticket, 'updates' => $updates]);
    }

    public function rentPot(): View
    {
        $data = $this->baseData();
        return view('rent-pot', $data);
    }

    public function buildingVibe(): View
    {
        $data = $this->baseData();
        return view('building-vibe', $data);
    }

    public function escrow(): View
    {
        $data = $this->baseData();
        $escrow = DB::table('escrow_cases')
            ->join('units', 'units.id', '=', 'escrow_cases.unit_id')
            ->select('escrow_cases.*', 'units.label as unit_label')
            ->latest('opened_at')
            ->first();
        $events = $escrow ? DB::table('escrow_events')->where('escrow_case_id', $escrow->id)->latest()->get() : collect();

        return view('escrow', [...$data, 'escrow' => $escrow, 'events' => $events]);
    }

    public function flagFeedback(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:500'],
            'fingerprint' => ['required', 'string', 'max:100'],
        ]);

        $property = DB::table('properties')->first();

        if ($property) {
            DB::table('building_feedback')->insert([
                'property_id' => $property->id,
                'user_id' => Auth::id(),
                'category' => $data['category'],
                'message' => $data['message'],
                'fingerprint' => $data['fingerprint'],
                'is_anonymous' => true,
                'flagged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('status', 'Anonymous building feedback submitted.');
    }

    private function baseData(): array
    {
        $property = DB::table('properties')->first();
        $unit = DB::table('units')->first();
        $score = DB::table('rent_scores')->where('user_id', Auth::id())->first()
            ?? DB::table('rent_scores')->orderByDesc('score')->first();
        $rentPot = DB::table('rent_pots')->first();
        $members = $rentPot
            ? DB::table('rent_pot_members')->join('users', 'users.id', '=', 'rent_pot_members.user_id')
                ->where('rent_pot_id', $rentPot->id)
                ->select('rent_pot_members.*', 'users.name')
                ->get()
            : collect();

        $paidTotal = (int) $members->sum('paid_amount');
        $rentTotal = (int) ($rentPot->total_amount ?? 0);
        $paidPercent = $rentTotal > 0 ? round(($paidTotal / $rentTotal) * 100) : 0;

        $feedback = DB::table('building_feedback')
            ->select('fingerprint', 'category', DB::raw('COUNT(*) as total'), DB::raw('MAX(flagged_at) as latest_flag'))
            ->groupBy('fingerprint', 'category')
            ->orderByDesc('total')
            ->get();

        $ticket = DB::table('maintenance_tickets')->latest('created_at')->first();
        $payments = DB::table('rent_payments')->get();

        return [
            'user' => Auth::user(),
            'property' => $property,
            'unit' => $unit,
            'score' => $score,
            'rewards' => $score?->rewards ? json_decode($score->rewards, true) : [],
            'rentPot' => $rentPot,
            'members' => $members,
            'paidTotal' => $paidTotal,
            'rentTotal' => $rentTotal,
            'paidPercent' => $paidPercent,
            'feedback' => $feedback,
            'ticket' => $ticket,
            'payments' => $payments,
            'paidPayments' => $payments->where('status', 'paid')->count(),
            'pendingPayments' => $payments->where('status', 'pending')->count(),
        ];
    }
}
