<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Volunteering extends Model
{

    protected $table = 'volunteerings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'location',
        'description',
        'file',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    /**
     * Get the user that owns the volunteering record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor for file URL.
     */
    public function getFileUrlAttribute()
    {
        return $this->file ? asset($this->file) : null;
    }
}
