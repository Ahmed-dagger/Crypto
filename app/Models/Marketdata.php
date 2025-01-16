<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marketdata extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'pair', 'latest_price', '24h_volume', '24h_high', '24h_low'
    ];

    // A market data entry can have many orders
    public function orders()
    {
        return $this->hasMany(Order::class, 'currency_pair', 'pair');
    }
}
