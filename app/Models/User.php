<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        
        'email',
        'password',
        'role',
        'status',
        'name',
        'two_factor_enabled',
        'google_id',
        'otp',
        'email_verified_at',
        'otp_expires_at',
    ];

    // A user can have many wallets
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    // A user can have many trades
    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    // A user can have many orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // A user can have many security logs
    public function securityLogs()
    {
        return $this->hasMany(SecurityLog::class);
    }

    // A user can have many sessions
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    // A user can have many notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }



}
