<?php

namespace App\Models;

use App\Enums\Fulfillment\FulfillmentServiceEnum;
use App\Enums\Fulfillment\FulfillmentStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Fulfillment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'service' => FulfillmentServiceEnum::class,
        'status' => FulfillmentStatusEnum::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(FulfillmentItem::class);
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->status)) $model->status = FulfillmentStatusEnum::待出貨;
        });

        static::saved(function (Model $model) {
            event(new \App\Events\OrderSavedFulfillmentStatus($model->order));
        });
    }
}
