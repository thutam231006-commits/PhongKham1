<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung đủ 8 phòng tư vấn để lễ tân xếp phòng được cho các lịch trùng khung giờ.
     */
    public function up(): void
    {
        if (! Schema::hasTable('consultation_rooms')) {
            return;
        }

        $existing = DB::table('consultation_rooms')->pluck('name')->all();

        foreach (range(1, 8) as $index) {
            $name = 'Phòng tư vấn '.$index;

            if (in_array($name, $existing, true)) {
                continue;
            }

            DB::table('consultation_rooms')->insert([
                'name' => $name,
                'location' => 'Tầng 1 - Mind Guide',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('consultation_rooms')->whereIn('name', [
            'Phòng tư vấn 4',
            'Phòng tư vấn 5',
            'Phòng tư vấn 6',
            'Phòng tư vấn 7',
            'Phòng tư vấn 8',
        ])->delete();
    }
};
