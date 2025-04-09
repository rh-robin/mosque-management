<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipsCare extends Model
{
    // Specify the table name if it's not the plural of the model name
    protected $table = 'tips_cares';

    // Mass assignable attributes
    protected $fillable = [
        'title',
        'sub_title',
        'image',
        'content',
    ];
}
