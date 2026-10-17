<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('is_active');
            $table->text('approval_reason')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approval_reason');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('approved_by')->nullable()->after('rejected_at')
                ->constrained('users')->nullOnDelete();
        });

        // Existing accounts predate the approval workflow and are treated as approved.
        DB::table('users')->whereNull('status')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approval_reason', 'approved_at', 'rejected_at']);
        });
    }
};
