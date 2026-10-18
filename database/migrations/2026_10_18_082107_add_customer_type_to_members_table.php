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
        Schema::table('members', function (Blueprint $table) {
            $table->string('customer_type')->default('member')->index()->after('email');
            $table->string('student_id_number')->nullable()->after('customer_type');
            $table->text('notes')->nullable()->after('student_id_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['customer_type', 'student_id_number', 'notes']);
        });
    }
};
