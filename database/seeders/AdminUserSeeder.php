<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = (string) config('admin.email', 'admin@brasiliaracing.local');
        $adminName = (string) config('admin.name', 'Admin Brasília Racing');
        $adminPassword = (string) config('admin.password', 'admin123456');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}
