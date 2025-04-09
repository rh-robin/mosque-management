<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    protected $table = 'sent_messages';
    protected $fillable = [
        'user_id',
        'username'
    ];
}
