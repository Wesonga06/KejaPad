<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_deposits', function (Blueprint $table) {
            $table->text('exception_reason')->nullable()->after('note');
            $table->timestamp('exception_approved_at')->nullable()->after('exception_reason');
            $table->foreignId('exception_approved_by')->nullable()->after('exception_approved_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('security_deposits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exception_approved_by');
            $table->dropColumn(['exception_reason', 'exception_approved_at']);
        });
    }
};
