<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(50)->create();

        User::create([
            'name' => 'Ahmed',  // Add a name for the user
            'email' => 'ahmed@gmail.com',
            'password' => Hash::make('12345678'), // Hash the password before saving it
        ]);
    }
}
