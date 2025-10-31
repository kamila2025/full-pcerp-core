<?php

namespace App\Models;

use App\Enums\PurchaseOrder\PurchaseOrderStatusEnum;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price'             => 'integer',
        'cost_price'        => 'integer',
        'subtotal'          => 'integer',
        'total_discount'    => 'integer',
        'total_tax'         => 'integer',
        'total_amount'      => 'integer',
        'properties'        => 'array',
    ];

    protected $appends = [
        'purchasing_quantity',
        'received_quantity',
    ];

    public function templateItem()
    {
        return $this->belongsTo(FormTemplateItem::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function fulfillmentItems()
    {
        return $this->hasMany(FulfillmentItem::class);
    }

    public function inventories()
    {
        return $this->hasManyThrough(Inventory::class, Variant::class, 'id', 'variant_id', 'variant_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function getPurchasingQuantityAttribute(): int
    {
        return $this->purchaseOrderItems()
            ->whereRelation('purchaseOrder', fn ($query) => $query->whereIn('status', [PurchaseOrderStatusEnum::新開單, PurchaseOrderStatusEnum::進貨中]))
            ->sum('quantity') ?? 0;
    }

    public function getReceivedQuantityAttribute(): int
    {
        return $this->purchaseOrderItems()
            ->whereRelation('purchaseOrder', 'status', PurchaseOrderStatusEnum::已完成)
            ->sum('quantity') ?? 0;
    }

    protected static function booted()
    {
        static::saving(function (Model $model) {
            $model->subtotal = $model->price * $model->quantity;

            $model->total_amount = $model->subtotal - $model->total_discount + $model->total_tax;

            if ($model->total_amount < 0) $model->total_amount = 0;
        });
    }
}
