<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createAdminUser();

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }

    /**
     * Create the configured administrator, granting every permission.
     *
     * Only runs when both an email and password are present in the config,
     * so installs opt in explicitly via the environment.
     */
    private function createAdminUser(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (blank($email) || blank($password)) {
            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        User::factory()->admin()->create([
            'name' => config('admin.name') ?: 'Admin',
            'email' => $email,
            'password' => $password,
        ]);
    }
}
