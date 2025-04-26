<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $table = 'donations';

    protected $fillable = [
        'user_id',
        'cause',
        'description',
        'image',
        'raised_amont',
        'amount_limit',
        'has_limit',
        'status'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
