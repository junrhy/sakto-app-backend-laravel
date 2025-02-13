<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where('email', 'client@sakto.app')->exists()) {
            User::create([
                'name' => 'Client',
                'email' => 'client@sakto.app',
                'password' => Hash::make('password'),
            ]);
        }

        if (!User::where('email', 'admin@sakto.app')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@sakto.app',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
