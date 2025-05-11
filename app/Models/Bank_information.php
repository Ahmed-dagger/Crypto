<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank_information extends Model
{
    protected $table = 'banking_informations';
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
