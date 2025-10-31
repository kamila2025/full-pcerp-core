<?php

namespace App\Models;

use App\Enums\Address\AddressTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $guarded = [];

    protected $casts = [
        'address_type' => AddressTypeEnum::class,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (isset($model->customer)) $model->address_type = AddressTypeEnum::客戶;
        });
    }
}
