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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('failed_login_count')->default(0)->after('status');
            $table->timestamp('locked_until')->nullable()->after('failed_login_count')->index();
        });

        Schema::create('customer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('customer_code', 24)->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('privacy_policy_version', 30)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('specialist_profile_id')->nullable()->constrained('specialist_profiles')->cascadeOnDelete();
            $table->string('type', 24)->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason', 255);
            $table->boolean('is_emergency')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['specialist_profile_id', 'start_date', 'end_date'], 'schedule_exception_lookup_index');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('rescheduled_from_id')->nullable()->after('service_id')->constrained('appointments')->nullOnDelete();
            $table->unsignedTinyInteger('reschedule_count')->default(0)->after('rescheduled_from_id');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_method')->index();
            $table->string('idempotency_key', 80)->nullable()->after('payment_expires_at');
            $table->unique(['customer_id', 'idempotency_key']);
        });

        Schema::create('reschedule_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->dateTime('requested_start_at');
            $table->dateTime('requested_end_at');
            $table->string('status', 24)->default('PENDING')->index();
            $table->string('reason', 500)->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['appointment_id', 'status']);
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('specialist_profile_id')->constrained('specialist_profiles')->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('VISIBLE')->index();
            $table->string('moderation_reason', 500)->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->index(['specialist_profile_id', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('MANUAL');
            $table->string('external_id', 100)->unique();
            $table->string('transfer_type', 10)->default('in');
            $table->decimal('amount', 12, 0);
            $table->string('content', 500)->nullable();
            $table->string('status', 30)->index();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 0);
            $table->unsignedTinyInteger('percentage');
            $table->string('status', 24)->default('PENDING')->index();
            $table->text('bank_name')->nullable();
            $table->text('bank_account')->nullable();
            $table->text('account_holder')->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 40)->default('general')->index();
            $table->string('label', 150);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 150);
            $table->string('group', 50)->index();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('reschedule_requests');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropUnique(['customer_id', 'idempotency_key']);
            $table->dropForeign(['rescheduled_from_id']);
            $table->dropColumn(['rescheduled_from_id', 'reschedule_count', 'payment_expires_at', 'idempotency_key']);
        });

        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('customer_profiles');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['locked_until']);
            $table->dropColumn(['failed_login_count', 'locked_until']);
        });
    }
};
