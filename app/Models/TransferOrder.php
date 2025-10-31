<?php

namespace App\Models;

use App\Enums\TransferOrder\TransferOrderArrivalStatusEnum;
use App\Enums\TransferOrder\TransferOrderStatusEnum;
use Illuminate\Database\Eloquent\Model;

class TransferOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'arrival_status' => TransferOrderArrivalStatusEnum::class,
        'status' => TransferOrderStatusEnum::class,
    ];

    public function items()
    {
        return $this->hasMany(TransferOrderItem::class);
    }

    public function transferUser()
    {
        return $this->belongsTo(User::class, 'transfer_user_id')->withTrashed();
    }

    public function receiverUser()
    {
        return $this->belongsTo(User::class, 'receiver_user_id')->withTrashed();
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (empty($model->arrival_status)) $model->arrival_status = TransferOrderArrivalStatusEnum::未到貨;

            if (empty($model->status)) $model->status = TransferOrderStatusEnum::新開單;
        });

        static::updating(function (Model $model) {
            if (!$model->status->canEdit()) throw new \Exception('調撥單已經完成，無法修改');
        });

        static::deleting(function (Model $model) {
            if (!$model->status->canDelete()) throw new \Exception('調撥單已經完成，無法刪除');
        });
    }
}