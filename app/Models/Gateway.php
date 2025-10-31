<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sub_gateways' => 'array',
        'additional' => 'array',
        'is_enabled' => 'boolean',
    ];
}
