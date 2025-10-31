<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdjustmentOrderItem extends Model
{
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function adjustmentOrder()
    {
        return $this->belongsTo(AdjustmentOrder::class);
    }
}
