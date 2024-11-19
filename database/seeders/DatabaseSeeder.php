<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Run MovieSeeder to seed movies and reviews
        $this->call(MovieSeeder::class);
    }
}
