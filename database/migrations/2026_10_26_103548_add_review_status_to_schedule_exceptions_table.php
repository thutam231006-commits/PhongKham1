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
        Schema::table('schedule_exceptions', function (Blueprint $table): void {
            // Trạng thái duyệt của ngoại lệ: Lễ tân tiếp nhận và xử lý các lịch
            // hẹn bị ảnh hưởng trước khi ngoại lệ có hiệu lực (Specialist Module §2.4).
            $table->string('review_status', 24)->default('APPROVED')->after('reason');
            $table->unsignedSmallInteger('affected_appointments_count')->default(0)->after('review_status');
            $table->text('resolution_note')->nullable()->after('affected_appointments_count');
            $table->timestamp('reviewed_at')->nullable()->after('resolution_note');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_exceptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'affected_appointments_count', 'resolution_note', 'reviewed_at']);
        });
    }
};
