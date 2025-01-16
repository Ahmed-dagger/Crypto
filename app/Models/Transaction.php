<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id', 'transaction_type', 'amount', 'currency', 'status'
    ];

    // A transaction belongs to one wallet
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
