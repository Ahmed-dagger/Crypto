<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $user = User::where('email', 'ahmed@gmail.com')->first();

        if ($user) {
            Wallet::updateOrCreate(
                ['user_id' => $user->id, 'currency' => 'USDT'],
                ['balance' => 1000000] // 1,000,000 USDT
            );

            $this->command->info("Wallet with 1,000,000 USDT created for {$user->name}.");
        } else {
            $this->command->warn("User 'ahmed@gmail.com' not found. Run UserSeeder first.");
        }
    }
}
