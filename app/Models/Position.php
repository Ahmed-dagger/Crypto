<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'user_id',
        'futures_wallet_id',
        'currency',
        'direction',
        'entry_price',
        'size',
        'leverage',
        'margin',
        'unrealized_pnl',
        'is_open'
    ];

    public function wallet()
    {
         return $this->belongsTo(FutureWallet::class, 'futures_wallet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
