<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FutureWallet extends Model
{
    protected $table = 'future_wallets';
     protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'margin'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
