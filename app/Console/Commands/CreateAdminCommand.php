<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'novara:create-admin
                            {--name= : Administrator display name}
                            {--email= : Administrator email address}';

    protected $description = 'Create or promote a NovaNews administrator account';

    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Name')));
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        $existing = User::query()->where('email', $email)->first();

        if ($existing instanceof User) {
            if ($existing->is_admin) {
                $this->warn('This account is already an administrator.');

                return self::SUCCESS;
            }

            if (! $this->confirm('This account already exists. Promote it to administrator?', true)) {
                return self::FAILURE;
            }

            $existing->forceFill([
                'is_admin' => true,
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ])->save();

            $this->info('Administrator access granted.');

            return self::SUCCESS;
        }

        $password = (string) $this->secret('Password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->info('Administrator created successfully.');

        return self::SUCCESS;
    }
}

