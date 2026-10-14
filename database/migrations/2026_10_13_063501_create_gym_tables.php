<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('member')->index();
            $t->boolean('is_active')->default(true);
        });
        Schema::create('members', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->string('member_number')->unique();
            $t->string('full_name')->index();
            $t->string('phone')->unique();
            $t->string('email')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('membership_plans', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('duration_unit');
            $t->unsignedInteger('duration_value');
            $t->unsignedInteger('visit_limit')->nullable();
            $t->unsignedBigInteger('price_idr');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('plan_id')->constrained('membership_plans')->restrictOnDelete();
            $t->string('status')->default('pending');
            $t->timestamps();
            $t->unique(['member_id', 'plan_id']);
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('invoice_number')->unique();
            $t->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('subscription_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('total_idr');
            $t->json('snapshot');
            $t->string('status')->default('unpaid')->index();
            $t->uuid('request_key')->unique();
            $t->timestamps();
        });
        Schema::create('membership_periods', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('subscription_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('source_invoice_id')->unique()->constrained('invoices')->restrictOnDelete();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status')->default('active');
            $t->unsignedInteger('visit_limit')->nullable();
            $t->timestamps();
            $t->index(['member_id', 'status', 'starts_at', 'ends_at']);
        });
        Schema::create('staff_shifts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('active_staff_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('opening_cash_idr');
            $t->unsignedBigInteger('expected_cash_idr')->nullable();
            $t->unsignedBigInteger('actual_cash_idr')->nullable();
            $t->bigInteger('cash_difference_idr')->nullable();
            $t->dateTime('closed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('invoice_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignUuid('staff_shift_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('amount_idr');
            $t->string('method');
            $t->string('reference')->nullable();
            $t->dateTime('paid_at')->index();
            $t->timestamps();
        });
        Schema::create('checkins', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('membership_period_id')->constrained()->restrictOnDelete();
            $t->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $t->string('method')->default('manual');
            $t->dateTime('checked_in_at')->index();
            $t->timestamps();
            $t->index(['member_id', 'checked_in_at']);
        });
        Schema::create('checkin_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $t->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $t->string('result');
            $t->dateTime('attempted_at');
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('action');
            $t->string('entity_id');
            $t->json('metadata')->nullable();
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'checkin_attempts', 'checkins', 'payments', 'staff_shifts', 'membership_periods', 'invoices', 'subscriptions', 'membership_plans', 'members'] as $name) {
            Schema::dropIfExists($name);
        } Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
    }
};
