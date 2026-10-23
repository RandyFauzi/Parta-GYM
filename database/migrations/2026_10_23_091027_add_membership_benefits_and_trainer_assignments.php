<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable();
        });
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->json('benefits')->nullable();
        });
        Schema::create('membership_entitlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('membership_period_id')->constrained()->restrictOnDelete();
            $table->string('benefit_key', 40);
            $table->unsignedInteger('quota')->nullable();
            $table->timestamps();
            $table->unique(['membership_period_id', 'benefit_key'], 'period_benefit_unique');
        });
        foreach (['checkins', 'class_enrollments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignUuid('membership_entitlement_id')->nullable()->constrained()->restrictOnDelete();
            });
        }
        Schema::table('class_enrollments', fn (Blueprint $table) => $table->string('booking_source', 20)->default('legacy'));
        Schema::create('trainer_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('trainer_profile_id')->constrained()->restrictOnDelete();
            $table->string('activity', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->string('active_key', 60)->nullable()->unique();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->string('source', 20)->default('management');
            $table->timestamps();
            $table->index(['member_id', 'activity', 'revoked_at']);
        });
        $this->backfill();
    }

    public function backfill(): void
    {
        DB::transaction(function () {
            DB::table('membership_plans')->orderBy('id')->chunkById(200, function ($plans) {
                foreach ($plans as $plan) {
                    if ($plan->benefits === null) {
                        DB::table('membership_plans')->where('id', $plan->id)->update(['benefits' => json_encode($this->legacy($plan->plan_type, $plan->visit_limit))]);
                    }
                }
            });
            DB::table('membership_periods')->orderBy('id')->chunkById(200, function ($periods) {
                foreach ($periods as $period) {
                    $invoice = DB::table('invoices')->where('id', $period->source_invoice_id)->first();
                    $snapshot = json_decode($invoice->snapshot, true, 512, JSON_THROW_ON_ERROR);
                    $benefits = $snapshot['benefits'] ?? $this->legacy($period->plan_type, $period->visit_limit);
                    foreach ($benefits as $key => $quota) {
                        DB::table('membership_entitlements')->insertOrIgnore([
                            'id' => (string) Str::uuid(), 'membership_period_id' => $period->id,
                            'benefit_key' => $key, 'quota' => $quota, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $id = DB::table('membership_entitlements')->where('membership_period_id', $period->id)->where('benefit_key', $key)->value('id');
                        if ($key === 'gym_access') {
                            DB::table('checkins')->where('membership_period_id', $period->id)->whereNull('membership_entitlement_id')->update(['membership_entitlement_id' => $id]);
                        } else {
                            $enrollments = DB::table('class_enrollments')->where('membership_period_id', $period->id)->whereNull('membership_entitlement_id');
                            if ($key !== 'legacy_personal') {
                                [$activity, $kind] = explode('_', $key);
                                $enrollments->whereIn('training_session_id', DB::table('training_sessions')->where('activity', $activity)->where('kind', $kind)->select('id'));
                            } else {
                                $enrollments->whereIn('training_session_id', DB::table('training_sessions')->where('kind', 'personal')->select('id'));
                            }
                            $enrollments->update(['membership_entitlement_id' => $id]);
                        }
                    }
                }
            });
            /** Preserve the most recent legacy coaching relationship per activity without changing bookings. */
            DB::table('class_enrollments as e')->join('training_sessions as s', 's.id', '=', 'e.training_session_id')
                ->whereIn('e.status', ['confirmed', 'attended', 'no_show'])
                ->select('e.id', 'e.member_id', 's.trainer_profile_id', 's.activity', 's.starts_at')
                ->orderByDesc('s.starts_at')->orderBy('e.id')->lazy(200)->each(function ($row) {
                    if (DB::table('trainer_assignments')->where('member_id', $row->member_id)->where('activity', $row->activity)->exists()) {
                        return;
                    }
                    DB::table('trainer_assignments')->insertOrIgnore([
                        'id' => (string) Str::uuid(), 'member_id' => $row->member_id, 'trainer_profile_id' => $row->trainer_profile_id,
                        'activity' => $row->activity, 'starts_at' => now(), 'ends_at' => null, 'revoked_at' => null,
                        'active_key' => $row->member_id.':'.$row->activity, 'assigned_by' => null,
                        'reason' => 'Migrasi hubungan coaching terakhir dari jadwal lama.', 'source' => 'legacy',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                });
        });
    }

    /** @return array<string, int|null> */
    private function legacy(string $type, ?int $quota): array
    {
        return match ($type) {
            'pt' => ['legacy_personal' => $quota],
            'pilates' => ['pilates_group' => $quota],
            'all_access' => ['gym_access' => $quota, 'legacy_personal' => $quota],
            default => ['gym_access' => $quota],
        };
    }

    public function down(): void
    {
        foreach (['checkins', 'class_enrollments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('membership_entitlement_id');
            });
        }
        Schema::table('class_enrollments', fn (Blueprint $table) => $table->dropColumn('booking_source'));
        Schema::dropIfExists('trainer_assignments');
        Schema::dropIfExists('membership_entitlements');
        Schema::table('membership_plans', fn (Blueprint $table) => $table->dropColumn('benefits'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('permissions'));
    }
};
