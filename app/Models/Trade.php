<?php

namespace App\Models;

use Illuminate\Cache\HasCacheLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'buy_currency', 'sell_currency', 'buy_amount', 'sell_amount', 'price', 'fee', 'fee_currency', 'status'
    ];

    // A trade belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
