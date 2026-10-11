<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'cms:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create (or reset the password of) a super admin user';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name', 'Administrator');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password (min 10 chars, letters + numbers)');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'password' => ['required', Password::min(10)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = Role::where('is_super', true)->first();
        if (! $role) {
            $this->error('No super admin role found. Run "php artisan db:seed --force" first.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated').' super admin '.$email.'.');

        return self::SUCCESS;
    }
}
