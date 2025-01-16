<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'token', 'device', 'ip_address', 'expires_at'
    ];

    // A session belongs to one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
