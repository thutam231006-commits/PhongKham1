<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bổ sung cặp trường dành riêng cho khách hàng (bản tóm lược đã lọc) —
     * BR-049/BR-053: `summary` và `follow_up_plan` là nội dung chuyên môn nội bộ,
     * chỉ chuyên viên phụ trách được đọc; khách hàng chỉ đọc cặp trường này.
     */
    public function up(): void
    {
        Schema::table('consultation_records', function (Blueprint $table): void {
            $table->text('customer_summary')->nullable()->after('follow_up_plan');
            $table->text('customer_recommendation')->nullable()->after('customer_summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_records', function (Blueprint $table): void {
            $table->dropColumn(['customer_summary', 'customer_recommendation']);
        });
    }
};
