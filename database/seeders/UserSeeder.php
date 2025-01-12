<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create worker account
        User::create([
            'first_name' => 'Worker ',
            'last_name' => ' User',
            'email' => 'test1@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'worker',
        ]);

        // Create client account
        User::create([
            'first_name' => 'Client ',
            'last_name' => ' User',
            'email' => 'test2@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'client',
        ]);
    }
}
