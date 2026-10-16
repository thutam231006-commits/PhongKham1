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
        Schema::table('specialist_profiles', function (Blueprint $table): void {
            $table->string('avatar_url', 500)->nullable()->after('user_id');
            $table->text('credentials')->nullable()->after('expertise');
            $table->unsignedInteger('completed_appointments')->default(0)->after('years_of_experience');
            $table->decimal('average_rating', 2, 1)->default(0)->after('completed_appointments');
            $table->decimal('consultation_fee', 12, 0)->nullable()->after('average_rating');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->string('image_url', 500)->nullable()->after('description');
            $table->text('target_audience')->nullable()->after('image_url');
            $table->text('session_process')->nullable()->after('target_audience');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable()->after('needs_attention');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancel_reason', 500)->nullable()->after('cancelled_at');
            $table->index(['status', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_status_start_at_index');
            $table->dropColumn(['confirmed_at', 'completed_at', 'cancelled_at', 'cancel_reason']);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['image_url', 'target_audience', 'session_process']);
        });

        Schema::table('specialist_profiles', function (Blueprint $table): void {
            $table->dropColumn(['avatar_url', 'credentials', 'completed_appointments', 'average_rating', 'consultation_fee']);
        });
    }
};
