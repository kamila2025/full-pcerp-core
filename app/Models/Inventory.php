<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventory extends Model
{
    use LogsActivity;

    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->hasOneThrough(Product::class, Variant::class, 'id', 'id', 'variant_id', 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function logs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['quantity']);
    }

    protected static function booted()
    {
        static::deleting(function ($model) {
            $model->activities()->delete();

            $model->logs()->delete();
        });
    }
}
