<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plans';


    protected $fillable = [
        'uuid',
        'stripe_plan_id',
        'plan_name',
        'plan_price',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
