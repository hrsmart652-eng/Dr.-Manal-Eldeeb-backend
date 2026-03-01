<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Test student
        User::create([
            'name' => 'أحمد محمد',
            'email' => 'student@leadersacademy.com',
            'password' => Hash::make('password'),
            'type' => 'student',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Test admin
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@leadersacademy.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }
}