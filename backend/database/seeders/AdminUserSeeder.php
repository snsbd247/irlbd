<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first super admin from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD.
 * Nothing is created when those variables are missing or when an admin already exists,
 * so no default credentials ever ship with the application.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('cms.initial_admin.email');
        $password = config('cms.initial_admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set: no admin user created. Use "php artisan cms:create-admin".');

            return;
        }

        if (strlen($password) < 10) {
            $this->command?->error('ADMIN_PASSWORD must be at least 10 characters. No admin user created.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Admin {$email} already exists, skipped.");

            return;
        }

        User::create([
            'name' => config('cms.initial_admin.name') ?: 'Administrator',
            'email' => $email,
            'password' => $password,
            'role_id' => Role::where('slug', 'super-admin')->value('id'),
            'is_active' => true,
        ]);

        $this->command?->info("Super admin {$email} created. Remove ADMIN_PASSWORD from .env now.");
    }
}
