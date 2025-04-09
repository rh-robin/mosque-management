<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breed extends Model
{
    protected $table = 'breeds';
    protected $guarded = ['id'];

    public function characteristics()
    {
        return $this->hasMany(Characteristic::class);
    }
}
