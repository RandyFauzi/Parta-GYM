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
        Schema::create('member_progress_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('training_session_id')->nullable()->constrained('training_sessions')->nullOnDelete();
            $table->dateTime('recorded_at')->index();

            // Metrik fisik
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('body_fat_percentage', 4, 1)->nullable();
            $table->decimal('muscle_mass_kg', 5, 2)->nullable();
            $table->decimal('chest_cm', 5, 1)->nullable();
            $table->decimal('waist_cm', 5, 1)->nullable();
            $table->decimal('hips_cm', 5, 1)->nullable();
            $table->decimal('arms_cm', 5, 1)->nullable();

            // Catatan latihan & evaluasi
            $table->text('workout_summary');
            $table->text('diet_summary')->nullable();
            $table->text('coach_notes')->nullable();
            $table->text('next_goals')->nullable();

            $table->timestamps();

            $table->index(['member_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_progress_records');
    }
};
