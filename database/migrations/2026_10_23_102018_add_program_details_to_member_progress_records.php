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
        Schema::table('member_progress_records', function (Blueprint $table) {
            $table->string('activity', 20)->nullable();
            $table->string('program_name', 120)->nullable();
            $table->json('exercises')->nullable();
            $table->uuid('request_key')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_progress_records', function (Blueprint $table) {
            $table->dropUnique(['request_key']);
            $table->dropColumn(['activity', 'program_name', 'exercises', 'request_key']);
        });
    }
};
