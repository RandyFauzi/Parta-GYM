<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateOwner extends Command
{
    protected $signature = 'gym:create-owner {email} {--name=Gym Owner}';

    protected $description = 'Create the first owner securely without a default password';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->secret('Password (minimum 12 characters)');
        $validator = Validator::make(['email' => $email, 'password' => $password], ['email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $user = new User;
        $user->name = $this->option('name');
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'owner';
        $user->save();
        $this->info('Owner created. Login with the credentials you entered.');

        return self::SUCCESS;
    }
}
