<?php

namespace App\Services;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Events\InventoryChanged;
use App\Models\Order;
use App\Models\Variant;

class OrderService
{
    /**
     * 刪除訂單項目
     *
     * @param int $orderId
     * @param array|string $orderItemIds 傳入 'all' 時刪除所有項目
     * @return void
     */
    public function deleteOrderItems(int $orderId, array|string $orderItemIds = 'all')
    {
        $order = Order::query()->findOrFail($orderId);

        $query = $order->items();

        if ($orderItemIds !== 'all') $query->whereNotIn('id', $orderItemIds);

        $query->each(function ($modelOrderItem) {
            event(new InventoryChanged(
                type: InventoryLogTypeEnum::刪除商品交易,
                variantId: $modelOrderItem?->variant_id,
                locationId: $modelOrderItem?->order?->shipping_location_id,
                quantity: $modelOrderItem->quantity,
                causer: auth()->user(),
                reference: $modelOrderItem,
                properties: $modelOrderItem->load('order:id,order_number')->toArray(),
            ));

            $modelOrderItem->delete();
        });
    }

    /**
     * 更新訂單項目
     *
     * @param array $orderItems
     * @param int $orderId
     * @return void
     */
    public function updateOrderItems(array $orderItems = [], int|Order $orderId)
    {
        $order = $orderId instanceof Order ? $orderId : Order::query()->findOrFail($orderId);

        // 刪除不存在的訂單項目
        $this->deleteOrderItems($order->getKey(), array_filter(array_column($orderItems, 'id')));

        // 取得訂單項目
        $variants = Variant::query()
            ->with('product', 'inventories')
            ->whereKey(array_column($orderItems, 'variant_id'))
            ->get();

        // 更新訂單項目
        foreach ($orderItems as $orderItem) {
            $variant = $variants->where('id', $orderItem['variant_id'] ?? null)->first();

            $originalQuantity = $order->items()->where('id', $orderItem['id'] ?? null)->value('quantity') ?? 0;

            $modelOrderItem = $order->items()->updateOrCreate(['id' => $orderItem['id'] ?? null], [
                'template_item_id'  => $orderItem['template_item_id'],
                'product_id'        => $variant['product_id'] ?? null,
                'variant_id'        => $variant['id'] ?? null,
                'product_name'      => $orderItem['product_name'] ?? $variant['product']['name'] ?? null,
                'variant_name'      => $orderItem['variant_name'] ?? $variant['name'] ?? null,
                'price'             => $orderItem['price'] ?? $variant['product']['price'] ?? 0,
                'cost_price'        => $orderItem['cost_price'] ?? $variant['product']['cost_price'] ?? 0,
                'quantity'          => $orderItem['quantity'],
            ]);

            if ($modelOrderItem->wasRecentlyCreated) {
                $modelOrderItem->fill([
                    'properties' => [
                        'categories' => $variant?->product?->categories?->pluck('name')?->toArray(),
                        'attribute_name' => $modelOrderItem?->templateItem?->attribute_name,
                    ],
                ]);

                $modelOrderItem->save();
            }

            if ($order->status === OrderStatusEnum::草稿) continue;

            // 庫存異動事件
            event(new InventoryChanged(
                type: $modelOrderItem->wasRecentlyCreated ? InventoryLogTypeEnum::商品交易 : InventoryLogTypeEnum::編輯商品交易,
                variantId: $variant?->id,
                locationId: $order?->shipping_location_id,
                quantity: $modelOrderItem->wasRecentlyCreated ? $orderItem['quantity'] : $originalQuantity - $orderItem['quantity'], // 負數表示扣除庫存
                causer: auth()->user(),
                reference: $modelOrderItem,
                properties: $modelOrderItem->load('order:id,order_number')->toArray(),
            ));
        }
    }

    /**
     * 更新訂單運費
     *
     * @param array $shippingFees
     * @param int $orderId
     * @return void
     */
    public function updateOrderFees(array $shippingFees, int $orderId)
    {
        $order = Order::query()->findOrFail($orderId);

        //
        $feeIds = $order->shippingFees()->pluck('id')->toArray();

        //
        $newFeeIds = array_filter(array_column($shippingFees, 'id'));

        //
        $order->shippingFees()->whereIn('id', array_diff($feeIds, $newFeeIds))->delete();

        //
        foreach ($shippingFees as $shippingFee) {
            $order->shippingFees()->updateOrCreate(['id' => $shippingFee['id'] ?? null], [
                'logistic_id'       => $shippingFee['logistic_id'] ?? null,
                'name'              => $shippingFee['name'] ?? '',
                'price'             => $shippingFee['price'] ?? 0,
                'total_discount'    => $shippingFee['total_discount'] ?? 0,
            ]);
        }
    }
}
