<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->text('bio')->nullable();
            $table->string('specialization', 200)->nullable();
            $table->text('qualifications')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->timestamps();
        });
        Schema::create('studio_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
            $table->unsignedInteger('capacity');
            $table->boolean('is_exclusive')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 120);
            $table->string('activity');
            $table->string('kind');
            $table->foreignUuid('trainer_profile_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('studio_room_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('capacity');
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['trainer_profile_id', 'starts_at', 'ends_at']);
            $table->index(['studio_room_id', 'starts_at', 'ends_at']);
            $table->index(['status', 'starts_at']);
        });
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('training_session_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('confirmed');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('attended_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['training_session_id', 'member_id']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['class_enrollments', 'training_sessions', 'studio_rooms', 'trainer_profiles'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
