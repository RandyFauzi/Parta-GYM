<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table): void {
            $table->json('availability')->nullable();
            $table->unsignedSmallInteger('session_buffer_minutes')->default(0);
        });
        Schema::create('trainer_time_offs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('trainer_profile_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason', 500);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['trainer_profile_id', 'starts_at', 'ends_at']);
        });
        Schema::create('gym_operating_hours', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('closed')->default(true);
            $table->string('opens_at', 5)->nullable();
            $table->string('closes_at', 5)->nullable();
            $table->timestamps();
        });
        Schema::table('checkins', function (Blueprint $table): void {
            $table->dateTime('checkout_due_at')->nullable()->index();
            $table->dateTime('checked_out_at')->nullable()->index();
            $table->string('checkout_method', 20)->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::create('membership_changes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('source_period_id')->constrained('membership_periods')->restrictOnDelete();
            $table->foreignUuid('invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('source_credit_idr');
            $table->unsignedBigInteger('applied_credit_idr');
            $table->unsignedBigInteger('amount_due_idr');
            $table->string('status', 20)->default('pending');
            $table->string('reason', 500);
            $table->json('source_snapshot');
            $table->uuid('request_key')->unique();
            $table->dateTime('activated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('member_credit_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('membership_change_id')->unique()->constrained()->restrictOnDelete();
            $table->bigInteger('amount_idr');
            $table->timestamps();
        });
        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach (['clock_in', 'clock_out'] as $phase) {
                $table->string($phase.'_photo_path')->nullable();
                $table->string($phase.'_photo_sha256', 64)->nullable();
                $table->dateTime($phase.'_evidence_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_credit_entries');
        Schema::dropIfExists('membership_changes');
        Schema::table('staff_attendances', function (Blueprint $table): void {
            foreach (['clock_in', 'clock_out'] as $phase) {
                $table->dropColumn([$phase.'_photo_path', $phase.'_photo_sha256', $phase.'_evidence_at']);
            }
        });
        Schema::table('checkins', function (Blueprint $table): void {
            $table->dropIndex(['checkout_due_at']);
            $table->dropIndex(['checked_out_at']);
            $table->dropConstrainedForeignId('checked_out_by');
            $table->dropColumn(['checkout_due_at', 'checked_out_at', 'checkout_method']);
        });
        Schema::dropIfExists('gym_operating_hours');
        Schema::dropIfExists('trainer_time_offs');
        Schema::table('trainer_profiles', fn (Blueprint $table) => $table->dropColumn(['availability', 'session_buffer_minutes']));
    }
};
