<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['action', 'created_at'], 'audit_logs_action_created_at_index');
            $table->index('entity_id', 'audit_logs_entity_id_index');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'invoices_status_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['method', 'paid_at'], 'payments_method_paid_at_index');
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->index('student_id_number', 'members_student_id_number_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_action_created_at_index');
            $table->dropIndex('audit_logs_entity_id_index');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_status_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_method_paid_at_index');
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex('members_student_id_number_index');
        });
    }
};
