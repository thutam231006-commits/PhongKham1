<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultation_rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('location', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique('name');
        });

        DB::table('consultation_rooms')->insert([
            ['name' => 'Phòng tư vấn 1', 'location' => 'Tầng 1 - Mind Guide', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Phòng tư vấn 2', 'location' => 'Tầng 1 - Mind Guide', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Phòng tư vấn 3', 'location' => 'Tầng 1 - Mind Guide', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_rooms');
    }
};
