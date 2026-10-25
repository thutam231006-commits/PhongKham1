<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung nội dung giới thiệu chi tiết cho gói dịch vụ: giới thiệu chung,
     * đối tượng phù hợp, quy trình buổi tư vấn và các cam kết kèm theo.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->text('introduction')->nullable()->after('description');
            $table->json('benefits')->nullable()->after('session_process');
            $table->json('symptoms')->nullable()->after('benefits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['introduction', 'benefits', 'symptoms']);
        });
    }
};
