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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_code', 24)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('specialist_profile_id')->constrained('specialist_profiles')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('buffer_minutes')->default(10);
            $table->decimal('price', 12, 0)->nullable();
            $table->string('status', 32)->default('CONFIRMED')->index();
            $table->string('payment_status', 32)->default('UNPAID')->index();
            $table->string('payment_method', 20)->default('CASH');
            $table->string('source', 20)->default('ONLINE');
            $table->text('customer_note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('slot_lock_key', 64)->nullable()->unique();
            $table->boolean('needs_attention')->default(false)->index();
            $table->timestamps();

            $table->index(['specialist_profile_id', 'start_at', 'end_at']);
            $table->index(['customer_id', 'status', 'start_at']);
        });

        Schema::create('appointment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('action', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20)->default('SYSTEM');
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['appointment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_status_histories');
        Schema::dropIfExists('appointments');
    }
};
