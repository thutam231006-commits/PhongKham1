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
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->decimal('refund_amount', 12, 0)->nullable()->after('refunded_at');
            $table->string('refund_status', 24)->nullable()->after('refund_amount')->index();
            $table->index(['paid_at', 'payment_status']);
            $table->index(['refunded_at', 'refund_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['paid_at', 'payment_status']);
            $table->dropIndex(['refunded_at', 'refund_status']);
            $table->dropColumn(['paid_at', 'refunded_at', 'refund_amount', 'refund_status']);
        });
    }
};
