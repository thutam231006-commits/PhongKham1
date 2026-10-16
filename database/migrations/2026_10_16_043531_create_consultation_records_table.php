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
        Schema::create('consultation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialist_profile_id')->constrained('specialist_profiles')->restrictOnDelete();
            $table->text('summary');
            $table->text('follow_up_plan')->nullable();
            $table->boolean('follow_up_needed')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique('appointment_id');
            $table->index(['specialist_profile_id', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_records');
    }
};
