<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $table = 'advertisements';

    protected $fillable = [
        'user_id',
        'image',
        'content',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
