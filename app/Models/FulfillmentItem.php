<?php

namespace App\Models;

use App\Enums\Order\OrderFulfillmentStatusEnum;
use Illuminate\Database\Eloquent\Model;

class FulfillmentItem extends Model
{
    protected $guarded = [];

    public function fulfillment()
    {
        return $this->belongsTo(Fulfillment::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    protected static function booted()
    {
        static::creating(function (Model $model) {
            if (!$orderItem = $model->orderItem) {
                throw new \Exception('找不到對應的訂單項目 (OrderItem)');
            }

            // 目前這筆 fulfilled_quantity
            $newQty = $model->fulfilled_quantity ?? 0;

            // 已經 fulfill 過的總數（同一個 order_item_id）
            $fulfilledSum = self::where('order_item_id', $model->order_item_id)->sum('fulfilled_quantity');

            $total = $fulfilledSum + $newQty;

            if ($total > $orderItem->quantity) {
                throw new \Exception("出貨數量超過原始訂單數量（原始：{$orderItem->quantity}，已出貨：{$fulfilledSum}，本次欲出貨：{$newQty}）");
            }
        });
    }
}
