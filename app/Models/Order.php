<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'currency_pair', 'order_type', 'amount', 'price', 'status'
    ];

    // An order belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // An order belongs to one market data entry
    public function marketData()
    {
        return $this->belongsTo(Marketdata::class, 'currency_pair', 'pair');
    }
}
