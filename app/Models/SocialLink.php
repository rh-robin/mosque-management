<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $table = 'social_links';

    protected $fillable = [
        'user_id',
        'title',
        'url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
