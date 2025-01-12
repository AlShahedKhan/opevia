<?php

namespace Database\Seeders;

use WorkerSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the UserSeeder
        $this->call(
            UserSeeder::class,
        );
    }
}
