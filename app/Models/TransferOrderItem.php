<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferOrderItem extends Model
{
    protected $guarded = [];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function transferOrder()
    {
        return $this->belongsTo(TransferOrder::class);
    }
}
