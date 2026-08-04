<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    private const FALLBACK_EMAIL = 'admin@example.test';

    private const FALLBACK_PASSWORD = 'Xk9#mPz2Qw7!';

    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email) {
            $email = self::FALLBACK_EMAIL;
            $this->command?->warn("ADMIN_EMAIL not set in .env — using fallback dev email: {$email}");
        }

        if (! $password) {
            $password = self::FALLBACK_PASSWORD;
            $this->command?->warn("ADMIN_PASSWORD not set in .env — using fallback dev password: {$password} (placeholder only, not a real credential — set ADMIN_PASSWORD in .env).");
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
            ]
        );

        $admin->is_admin = true;
        $admin->save();
    }
}
