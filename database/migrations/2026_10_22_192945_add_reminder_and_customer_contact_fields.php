<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yêu cầu 1: cột mốc nhắc lịch lần 2 (30 phút trước) + ngày sinh/giới tính/địa chỉ khách hàng.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('appointments', 'reminder_24h_at')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->timestamp('reminder_24h_at')->nullable()->after('reminded_at')->index();
                $table->timestamp('reminder_30m_at')->nullable()->after('reminder_24h_at')->index();
            });
        }

        if (! Schema::hasColumn('users', 'gender')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('gender', 20)->nullable()->after('phone');
                $table->string('address', 255)->nullable()->after('gender');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'reminder_30m_at')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->dropColumn(['reminder_24h_at', 'reminder_30m_at']);
            });
        }

        if (Schema::hasColumn('users', 'address')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn(['gender', 'address']);
            });
        }
    }
};
