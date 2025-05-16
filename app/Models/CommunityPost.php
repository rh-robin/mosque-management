<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityPost extends Model
{
    protected $fillable = [
        'user_id',
        'post',
    ];

    /**
     * Get the user that owns the community post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
