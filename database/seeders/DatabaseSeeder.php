<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        foreach ([
            'escrow_events', 'escrow_cases', 'building_feedback', 'rent_pot_members', 'rent_pots',
            'maintenance_updates', 'maintenance_tickets', 'rent_payments', 'rent_scores', 'tenancies',
            'units', 'properties', 'users',
        ] as $table) {
            DB::table($table)->delete();
        }

        DB::statement('PRAGMA foreign_keys = ON');

        $password = Hash::make('password');

        $landlord = User::create(['name' => 'Grace Mwangi', 'email' => 'landlord@kejapad.test', 'role' => 'landlord', 'phone' => '+254700100200', 'password' => $password]);
        $amina = User::create(['name' => 'Amina Wanjiku', 'email' => 'tenant@kejapad.test', 'role' => 'tenant', 'phone' => '+254711222333', 'password' => $password]);
        $brian = User::create(['name' => 'Brian Otieno', 'email' => 'brian@kejapad.test', 'role' => 'tenant', 'phone' => '+254722333444', 'password' => $password]);
        $chebet = User::create(['name' => 'Chebet Kiptoo', 'email' => 'chebet@kejapad.test', 'role' => 'tenant', 'phone' => '+254733444555', 'password' => $password]);
        $david = User::create(['name' => 'David Kariuki', 'email' => 'david@kejapad.test', 'role' => 'tenant', 'phone' => '+254744555666', 'password' => $password]);

        $propertyId = DB::table('properties')->insertGetId([
            'landlord_id' => $landlord->id,
            'name' => 'Kilimani Heights',
            'address' => 'Argwings Kodhek Road',
            'city' => 'Nairobi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitId = DB::table('units')->insertGetId([
            'property_id' => $propertyId,
            'label' => 'House B12',
            'monthly_rent' => 96000,
            'bedrooms' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$amina, $brian, $chebet, $david] as $tenant) {
            $tenancyId = DB::table('tenancies')->insertGetId([
                'unit_id' => $unitId,
                'tenant_id' => $tenant->id,
                'starts_at' => '2025-08-01',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('rent_payments')->insert([
                'tenancy_id' => $tenancyId,
                'user_id' => $tenant->id,
                'amount' => $tenant->is($david) ? 0 : 24000,
                'due_date' => now()->addDays(5)->toDateString(),
                'paid_at' => $tenant->is($david) ? null : now()->subDays(2),
                'status' => $tenant->is($david) ? 'pending' : 'paid',
                'reference' => $tenant->is($david) ? null : 'MPESA-KP'.str_pad((string) $tenant->id, 4, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('rent_scores')->insert([
                'user_id' => $tenant->id,
                'score' => $tenant->is($amina) ? 842 : ($tenant->is($david) ? 701 : 810),
                'on_time_payments' => $tenant->is($david) ? 7 : 14,
                'rewards' => json_encode(['Free carpet cleaning', 'Certified reference', 'Priority parking waitlist']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rentPotId = DB::table('rent_pots')->insertGetId([
            'unit_id' => $unitId,
            'month' => now()->format('F Y'),
            'total_amount' => 96000,
            'status' => 'collecting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([[$amina, 24000], [$brian, 24000], [$chebet, 24000], [$david, 4000]] as [$tenant, $paid]) {
            DB::table('rent_pot_members')->insert([
                'rent_pot_id' => $rentPotId,
                'user_id' => $tenant->id,
                'share_amount' => 24000,
                'paid_amount' => $paid,
                'paid_at' => $paid >= 24000 ? now()->subDays(2) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $ticketId = DB::table('maintenance_tickets')->insertGetId([
            'unit_id' => $unitId,
            'tenant_id' => $amina->id,
            'landlord_id' => $landlord->id,
            'title' => 'Kitchen sink leak',
            'description' => 'Water is leaking below the sink cabinet and spreading to the floor tiles.',
            'status' => 'dispatched',
            'priority' => 'high',
            'contractor_name' => 'Juma Maintenance',
            'contractor_rating' => 5,
            'eta_minutes' => 12,
            'created_at' => now()->subHours(3),
            'updated_at' => now(),
        ]);

        foreach ([
            ['Complaint received', 'Tenant submitted photos and water shutoff notes.', now()->subHours(3), 1],
            ['Landlord approved', 'Repair budget confirmed by Grace Mwangi.', now()->subHours(2), 2],
            ['Plumber dispatched', 'Juma Maintenance accepted the job and is en route.', now()->subMinutes(28), 3],
            ['Job completed', 'Awaiting tenant signoff after visit.', null, 4],
        ] as [$label, $details, $completedAt, $position]) {
            DB::table('maintenance_updates')->insert([
                'maintenance_ticket_id' => $ticketId,
                'label' => $label,
                'details' => $details,
                'completed_at' => $completedAt,
                'position' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            [$amina, 'Water pressure', 'Low water pressure in B block bathrooms.', 'water-low'],
            [$brian, 'Water pressure', 'Kitchen tap pressure is very low today.', 'water-low'],
            [$chebet, 'Water pressure', 'No pressure after 7am.', 'water-low'],
            [$david, 'Water pressure', 'Shower pressure is low.', 'water-low'],
            [$brian, 'Security', 'The gate was left open after 10pm.', 'gate-open'],
            [$chebet, 'Security', 'Main gate unattended for a while.', 'gate-open'],
            [$david, 'Generator noise', 'Generator noise after midnight.', 'generator-noise'],
        ] as [$user, $category, $message, $fingerprint]) {
            DB::table('building_feedback')->insert([
                'property_id' => $propertyId,
                'user_id' => $user->id,
                'category' => $category,
                'message' => $message,
                'fingerprint' => $fingerprint,
                'is_anonymous' => true,
                'flagged_at' => now()->subMinutes(rand(15, 180)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $escrowId = DB::table('escrow_cases')->insertGetId([
            'unit_id' => $unitId,
            'tenant_id' => $amina->id,
            'landlord_id' => $landlord->id,
            'title' => 'Ceiling leak dispute',
            'amount' => 48000,
            'status' => 'held',
            'opened_at' => now()->subDays(21),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['Repair ignored', 'Tenant reported ceiling leak repeatedly for 21 days.'],
            ['Funds verified', 'KSh 48,000 placed in escrow wallet as proof of ability to pay.'],
            ['Mediation ready', 'Evidence packet contains repair requests, photos, and notices.'],
        ] as [$label, $details]) {
            DB::table('escrow_events')->insert([
                'escrow_case_id' => $escrowId,
                'label' => $label,
                'details' => $details,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
