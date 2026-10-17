<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_transactions', function (Blueprint $table) {
            // A tenant's own comment / dispute on a proposed deduction. Kept on
            // the transaction so an earlier response is never silently erased.
            $table->text('tenant_comment')->nullable()->after('evidence');
            $table->boolean('disputed')->default(false)->after('tenant_comment');
            $table->timestamp('responded_at')->nullable()->after('disputed');
            // Highlights allocations that must be explicitly authorised (e.g.
            // applying a deposit to rent arrears) rather than applied by default.
            $table->boolean('requires_authorisation')->default(false)->after('disputed');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_transactions', function (Blueprint $table) {
            $table->dropColumn(['tenant_comment', 'disputed', 'responded_at', 'requires_authorisation']);
        });
    }
};
