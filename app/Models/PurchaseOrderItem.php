<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'cost_price' => 'integer',
        'total_cost' => 'integer',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function order()
    {
        return $this->hasOneThrough(Order::class, OrderItem::class, 'id', 'id', 'order_item_id', 'order_id');
    }
}
