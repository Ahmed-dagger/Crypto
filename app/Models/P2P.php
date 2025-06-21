<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class P2P extends Model implements HasMedia
{

    use HasFactory , InteractsWithMedia;

    protected $table = 'p2p';

    protected $fillable = [
        'user_id',
        'counterparty_id',
        'trade_type',
        'currency',
        'amount',
        'fiat_amount',
        'fiat_currency',
        'payment_method',
        'transfer_status',
        'note',
        'trade_reference',
        'payment_details',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'fiat_amount' => 'decimal:2',
    ];

    /**
     * The user who created the trade.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The counterparty involved in the trade (buyer or seller).
     */
    public function counterparty()
    {
        return $this->belongsTo(User::class, 'counterparty_id');
    }

    /**
     * Scope to filter trades by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('transfer_status', $status);
    }

    /**
     * Generate a UUID for trade_reference before creating.
     */
   
}
