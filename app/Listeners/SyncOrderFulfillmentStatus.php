<?php

namespace App\Listeners;

use App\Enums\Fulfillment\FulfillmentStatusEnum;
use App\Enums\Order\OrderFulfillmentStatusEnum;
use App\Events\OrderSavedFulfillmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncOrderFulfillmentStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderSavedFulfillmentStatus $event): void
    {
        if (!$order = $event->order) return;

        $orderItems = $order
            ->items()
            ->with([
                'fulfillmentItems' => fn ($query) => $query->whereRelation('fulfillment', 'status', FulfillmentStatusEnum::已出貨)
            ])
            ->get();

        $totalOrderedQty = 0;

        $totalFulfilledQty = 0;

        foreach ($orderItems as $item) {
            $totalOrderedQty += $item->quantity;
            $fulfilledQty = $item->fulfillmentItems->sum('fulfilled_quantity');
            $totalFulfilledQty += $fulfilledQty;
        }

        if ($totalFulfilledQty === 0) {
            // 尚未出貨
            $order->fulfillment_status = OrderFulfillmentStatusEnum::未出貨;
        } elseif ($totalFulfilledQty >= $totalOrderedQty && $totalOrderedQty > 0) {
            // 全部出貨
            $order->fulfillment_status = OrderFulfillmentStatusEnum::已出貨;
        } elseif ($totalOrderedQty > 0) {
            // 部分出貨
            $order->fulfillment_status = OrderFulfillmentStatusEnum::部分出貨;
        } else {
            // 尚未出貨，可選擇不變更
            return;
        }

        $order->save();
    }
}
