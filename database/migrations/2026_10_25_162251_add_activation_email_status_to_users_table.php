<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Theo dõi trạng thái gửi email kích hoạt tài khoản để lễ tân biết chính xác
     * vì sao khách hàng chưa nhận được email thay vì phải suy đoán.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('activation_email_status', 20)->default('NOT_SENT')->after('status');
            $table->timestamp('activation_email_sent_at')->nullable()->after('activation_email_status');
            $table->text('activation_email_error')->nullable()->after('activation_email_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['activation_email_status', 'activation_email_sent_at', 'activation_email_error']);
        });
    }
};
