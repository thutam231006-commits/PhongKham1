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
        if (! Schema::hasColumn('appointments', 'reminded_at')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->timestamp('reminded_at')->nullable()->after('payment_expires_at')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'reminded_at')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropColumn('reminded_at');
            });
        }
    }
};
