<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LinkMemberAccount extends Command
{
    protected $signature = 'gym:link-member {member_number} {email}';

    protected $description = 'Provision a member login after staff identity verification';

    public function handle(): int
    {
        $member = Member::where('member_number', $this->argument('member_number'))->first();
        if (! $member || $member->user_id) {
            $this->error('Member missing or already linked.');

            return self::FAILURE;
        }
        $email = $this->argument('email');
        $password = $this->secret('Member password (minimum 12 characters)');
        $validator = Validator::make(['email' => $email, 'password' => $password], ['email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        DB::transaction(function () use ($member, $email, $password) {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            if ($member->user_id) {
                throw new \RuntimeException('Already linked.');
            } $user = User::create(['name' => $member->full_name, 'email' => $email, 'password' => Hash::make($password)]);
            $member->update(['user_id' => $user->id]);
            app(MembershipService::class)->audit('member.account_linked', $member->id);
        });
        $this->info('Member login linked. Deliver credentials privately after identity verification.');

        return self::SUCCESS;
    }
}
