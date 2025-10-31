<?php

namespace App\Models;

use App\Enums\PurchaseOrder\PurchaseOrderArrivalStatusEnum;
use App\Enums\PurchaseOrder\PurchaseOrderStatusEnum;
use App\Enums\PurchaseOrder\PurchaseOrderTypeEnum;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => PurchaseOrderTypeEnum::class,
        'total_amount' => 'integer',
        'arrival_status' => PurchaseOrderArrivalStatusEnum::class,
        'status' => PurchaseOrderStatusEnum::class,
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'issuer_user_id')->withTrashed();
    }

    public function executorUser()
    {
        return $this->belongsTo(User::class, 'executor_user_id')->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->arrival_status)) $model->arrival_status = match ($model->type) {
                PurchaseOrderTypeEnum::進貨 => PurchaseOrderArrivalStatusEnum::未到貨,
                PurchaseOrderTypeEnum::退貨 => PurchaseOrderArrivalStatusEnum::未退貨,
            };

            if (empty($model->status)) $model->status = PurchaseOrderStatusEnum::新開單;
        });

        static::updating(function (Model $model) {
            if (!$model->status->canEdit()) throw new \Exception(match ($model->type) {
                PurchaseOrderTypeEnum::進貨 => '進貨單已經完成，無法修改',
                PurchaseOrderTypeEnum::退貨 => '退貨單已經完成，無法修改',
            });
        });

        static::deleting(function (Model $model) {
            if (!$model->status->canDelete()) throw new \Exception(match ($model->type) {
                PurchaseOrderTypeEnum::進貨 => '進貨單已經完成，無法刪除',
                PurchaseOrderTypeEnum::退貨 => '退貨單已經完成，無法刪除',
            });
        });
    }
}
