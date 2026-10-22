<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('checked_in_at')->nullable()->after('cancel_reason');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable()->after('checked_in_by');
            $table->foreignId('collected_by')->nullable()->after('collected_at')->constrained('users')->nullOnDelete();
            $table->string('payment_reference', 100)->nullable()->after('collected_by');
            $table->index(['payment_status', 'payment_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_payment_status_payment_method_status_index');
            $table->dropForeign(['checked_in_by']);
            $table->dropForeign(['collected_by']);
            $table->dropColumn(['checked_in_at', 'checked_in_by', 'collected_at', 'collected_by', 'payment_reference']);
        });
    }
};
