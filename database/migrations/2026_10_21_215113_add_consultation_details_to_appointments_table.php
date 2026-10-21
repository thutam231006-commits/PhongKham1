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
            $table->string('consultation_type', 10)->default('OFFLINE')->after('source')->index();
            $table->foreignId('consultation_room_id')->nullable()->after('consultation_type')->constrained('consultation_rooms')->nullOnDelete();
            $table->string('meeting_url', 500)->nullable()->after('consultation_room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['consultation_room_id']);
            $table->dropIndex('appointments_consultation_type_index');
            $table->dropColumn(['consultation_type', 'consultation_room_id', 'meeting_url']);
        });
    }
};
