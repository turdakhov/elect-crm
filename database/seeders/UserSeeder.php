<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            UserRoleEnum::Admin,
            UserRoleEnum::Manager,
            UserRoleEnum::Worker,
            UserRoleEnum::Client,
            UserRoleEnum::Designer,
            UserRoleEnum::Accountant,
            UserRoleEnum::Foreman,
        ];

        foreach ($roles as $role) {
            $count = match ($role) {
                UserRoleEnum::Admin => 2,
                UserRoleEnum::Manager => 3,
                UserRoleEnum::Worker => 3,
                UserRoleEnum::Client => 3,
                UserRoleEnum::Designer => 2,
                UserRoleEnum::Accountant => 2,
                UserRoleEnum::Foreman => 2,
            };

            for ($i = 1; $i <= $count; $i++) {
                User::firstOrCreate(
                    ['email' => strtolower($role->name) . $i . '@example.com'],
                    [
                        'name' => $role->name . ' ' . $i,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'role' => $role->name,
                    ]
                );
            }
        }
    }
}
