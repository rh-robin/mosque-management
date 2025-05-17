<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class React extends Model
{
    protected $fillable = [
        'user_id',
        'community_post_id',
        'type',
    ];

    /**
     * Get the user who reacted.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the community post that was reacted to.
     */
    public function communityPost()
    {
        return $this->belongsTo(CommunityPost::class);
    }
}
