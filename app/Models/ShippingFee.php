<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingFee extends Model
{
    protected $casts = [
        'price'             => 'integer',
        'total_discount'    => 'integer',
    ];

    protected $guarded = [];
}
