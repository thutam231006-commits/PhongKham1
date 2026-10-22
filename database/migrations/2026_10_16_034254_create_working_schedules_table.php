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
        if (! Schema::hasTable('working_schedules')) {
            Schema::create('working_schedules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('specialist_profile_id')->constrained('specialist_profiles')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->time('break_start')->nullable();
                $table->time('break_end')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $hasLookupIndex = collect(Schema::getIndexes('working_schedules'))
            ->contains(fn (array $index): bool => $index['name'] === 'working_schedule_lookup_index');

        if (! $hasLookupIndex) {
            Schema::table('working_schedules', function (Blueprint $table): void {
                $table->index(
                    ['specialist_profile_id', 'day_of_week', 'is_active'],
                    'working_schedule_lookup_index'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('working_schedules');
    }
};
