<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_periods', function (Blueprint $table) {
            $table->string('plan_type')->default('gym')->index();
        });
        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->foreignUuid('membership_period_id')->nullable()->constrained()->restrictOnDelete();
        });
        DB::table('invoices')->orderBy('id')->chunkById(200, function ($invoices) {
            foreach ($invoices as $invoice) {
                $snapshot = json_decode($invoice->snapshot, true, 512, JSON_THROW_ON_ERROR);
                $type = $snapshot['plan_type'] ?? DB::table('subscriptions')->join('membership_plans', 'membership_plans.id', '=', 'subscriptions.plan_id')->where('subscriptions.id', $invoice->subscription_id)->value('membership_plans.plan_type') ?? 'gym';
                if (! isset($snapshot['plan_type'])) {
                    $snapshot['plan_type'] = $type;
                    DB::table('invoices')->where('id', $invoice->id)->update(['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
                }
                DB::table('membership_periods')->where('source_invoice_id', $invoice->id)->update(['plan_type' => $type]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_period_id');
        });
        Schema::table('membership_periods', function (Blueprint $table) {
            $table->dropColumn('plan_type');
        });
    }
};
