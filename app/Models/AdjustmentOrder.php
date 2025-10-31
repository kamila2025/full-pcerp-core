<?php

namespace App\Models;

use App\Enums\AdjustmentOrder\AdjustmentOrderResultEnum;
use App\Enums\AdjustmentOrder\AdjustmentOrderStatusEnum;
use Illuminate\Database\Eloquent\Model;

class AdjustmentOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'result' => AdjustmentOrderResultEnum::class,
        'status' => AdjustmentOrderStatusEnum::class,
    ];

    public function items()
    {
        return $this->hasMany(AdjustmentOrderItem::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->result)) $model->result = AdjustmentOrderResultEnum::未完成;

            if (empty($model->status)) $model->status = AdjustmentOrderStatusEnum::新開單;
        });

        static::updating(function (Model $model) {
            if (!$model->status->canEdit()) throw new \Exception('盤點單已經完成，無法修改');
        });

        static::deleting(function (Model $model) {
            if (!$model->status->canDelete()) throw new \Exception('盤點單已經完成，無法刪除');
        });
    }
}
