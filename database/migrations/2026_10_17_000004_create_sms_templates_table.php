<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('body');
            $table->timestamps();
        });

        $now = now();

        DB::table('sms_templates')->insert([
            [
                'key' => 'landlord_approved',
                'name' => 'Landlord approved',
                'body' => 'Hi {{name}}, your KejaPad landlord account has been approved. You can now sign in and start managing your properties.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'landlord_rejected',
                'name' => 'Landlord rejected',
                'body' => 'Hi {{name}}, your KejaPad landlord application was not approved. Reason: {{reason}}.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'caretaker_invitation',
                'name' => 'Caretaker invitation',
                'body' => 'Hi {{name}}, you have been invited to join KejaPad as a caretaker. Use code {{code}} to register. This code expires on {{expires}}.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'rent_reminder',
                'name' => 'Rent reminder',
                'body' => 'Hi {{name}}, a rent payment of KES {{amount}} is due on {{due_date}}. Please make your payment to avoid arrears.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'arrears_notice',
                'name' => 'Arrears notice',
                'body' => 'Hi {{name}}, your rent account is overdue by KES {{amount}} ({{days}} days). Kindly settle your balance to avoid escalation.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'move_out_update',
                'name' => 'Move-out update',
                'body' => 'Hi {{name}}, your move-out request status is now: {{status}}.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
