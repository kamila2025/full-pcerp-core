<?php

namespace App\Models;

use App\Enums\Logistics\LogisticsProviderEnum;
use App\Enums\Logistics\LogisticsTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Logistics extends Model
{
    protected $guarded = [];

    protected $casts = [
        'provider' => LogisticsProviderEnum::class,
        'type' => LogisticsTypeEnum::class,
        'free_price' => 'integer',
        'fee' => 'integer',
        'is_enabled' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->position)) {
                $maxPosition = static::max('position');

                $model->position = is_null($maxPosition) ? 1 : $maxPosition + 1;
            }
        });
    }
}
