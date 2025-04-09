<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Characteristic extends Model
{
    protected $table = 'characteristics';
    protected $guarded = ['id'];


    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }
}
