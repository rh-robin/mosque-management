<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $table = 'pets';
    protected $guarded = ['id'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the breed associated with the pet.
     */
    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }
}
