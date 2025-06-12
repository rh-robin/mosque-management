<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{

    protected $table = 'faqs';

    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'status',
    ];

    /**
     * Get the user that created the FAQ.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
