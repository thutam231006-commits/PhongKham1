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
        Schema::create('specialist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('expertise', 255)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('years_of_experience')->nullable();
            $table->string('status', 20)->default('INACTIVE')->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'display_order']);
        });

        Schema::create('specialist_services', function (Blueprint $table) {
            $table->foreignId('specialist_profile_id')->constrained('specialist_profiles')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['specialist_profile_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialist_services');
        Schema::dropIfExists('specialist_profiles');
    }
};
