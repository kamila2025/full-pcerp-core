<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use Illuminate\Support\Facades\DB;

class OrderItemRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return OrderItem::class;
    }

    /**
     * 獲取可用於進貨單的訂單項目列表
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrderItemsForPurchasing($attributes = [])
    {
        return $this->model
            ->with('order:id,order_number,customer_id,location_id', 'order.customer:id,name', 'variant.product:id,name', 'inventories:location_id,variant_id,quantity')
            ->withSum('purchaseOrderItems', 'quantity')
            ->where(
                fn ($query) => $query
                    ->whereNull('variant_id')
                    ->orWhereRelation('variant.product', 'inventory_management', ProductInventoryManagementEnum::庫存管理)
            )
            ->whereRelation('order', 'status', OrderStatusEnum::開啟)
            ->tap(match (DB::getDriverName()) {
                'mysql' => fn ($query) => $query->havingRaw('order_items.quantity > COALESCE(purchase_order_items_sum_quantity, 0)'),
                'sqlite' => fn ($query) => $query->whereRaw('quantity > COALESCE(purchase_order_items_sum_quantity, 0)'),
            })
            ->orderBy(Order::select('created_at')->whereColumn('orders.id', 'order_items.order_id'), 'desc')
            ->get();
    }
}
