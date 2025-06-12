<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';

    protected $fillable = [
        'user_id',
        'community_post_id',
        'comment',
    ];

    /**
     * Get the user who made the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the community post that the comment belongs to.
     */
    public function communityPost()
    {
        return $this->belongsTo(CommunityPost::class);
    }
}
