<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Course::firstOrCreate(
            ['code' => 'CS101'],
            ['name' => 'Introduction to Programming']
        );
        $this->call(StaffSeeder::class);
    }
}
