<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Variant extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'price'             => 'integer',
        'compare_at_price'  => 'integer',
        'cost_price'        => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function values()
    {
        return $this->belongsToMany(VariantValue::class, 'variant_value_has_variant', 'variant_id', 'variant_value_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['created_at', 'updated_at']);
    }

    protected static function booted()
    {
        static::deleting(function (Model $model) {
            if ($model->orderItems()->exists()) throw new \Exception($model->product->name . ' ' . $model->name . ' 有訂單項目，無法刪除');
        });
    }
}
