<?php

namespace Database\Seeders;

use App\Models\Central\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->updateOrCreate(
            ['email' => 'admin@kurye.local'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
            ]
        );
    }
}
