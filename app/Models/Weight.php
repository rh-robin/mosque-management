<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weight extends Model
{
    protected $fillable = [
        'pet_id',
        'weight',
        'weight_goal',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'weight_goal' => 'decimal:2',
        'pet_id' => 'integer',
    ];

    /**
     * Get the pet that owns the weight record.
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
