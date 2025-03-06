<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
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

        User::create([
            'name' => 'Ahmed',  // Add a name for the user
            'email' => 'ahdbo124@gmail.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => Carbon::now(), // Hash the password before saving it
        ]);
    }
}
