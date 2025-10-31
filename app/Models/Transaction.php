<?php

namespace App\Models;

use App\Enums\Gateway\GatewayMethodEnum;
use App\Enums\Gateway\GatewayTypeEnum;
use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Events\OrderSavedFinancialStatus;
use App\Events\TransactionChanged;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'            => 'integer',
        'fee'               => 'integer',
        'additional'        => 'array',
        'gateway_type'      => GatewayTypeEnum::class,
        'gateway_method'    => GatewayMethodEnum::class,
        'status'            => TransactionStatusEnum::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            $model->number = now()->format('YmdHis');

            if (empty($model->status)) $model->status = TransactionStatusEnum::處理中;

            if (!$order = $model->order) throw new \Exception('訂單不存在');

            if ($order) {
                $totalTransactionAmount = $order->transactions->sum('amount');

                $orderAmount = $order->amount;

                if ($totalTransactionAmount + $model->amount > $orderAmount) {
                    throw new \Exception('交易金額超過訂單總金額');
                }
            }
        });

        static::created(function (Model $model) {
            event(new TransactionChanged($model, true));
        });

        static::updated(function (Model $model) {
            event(new TransactionChanged($model));
        });

        static::saved(function (Model $model) {
            event(new OrderSavedFinancialStatus($model->order));
        });
    }
}
