<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    public $table = 'favourite';
    protected $fillable = ['user_id','currency'];
}
