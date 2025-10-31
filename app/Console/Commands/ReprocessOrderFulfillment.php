<?php

namespace App\Console\Commands;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Events\InventoryChanged;
use App\Models\Order;
use App\Models\InventoryLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReprocessOrderFulfillment extends Command
{
    protected $signature = 'order:reprocess-inventory {order_number? : 訂單編號}';

    protected $description = '重新處理訂單的商品庫存扣除';

    public function handle()
    {
        $orderNumber = $this->argument('order_number');
        
        if (empty($orderNumber)) {
            $orderNumber = $this->ask('請輸入訂單編號');
        }

        if (empty($orderNumber)) {
            $this->error('訂單編號不能為空');
            return 1;
        }

        $order = Order::with(['items.variant.product', 'items.variant.inventories'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            $this->error('找不到指定的訂單');
            return 1;
        }

        // 檢查訂單中是否有需要庫存管理的商品
        $hasInventoryItems = $order->items->some(function ($item) {
            return $item->variant?->product?->inventory_management === ProductInventoryManagementEnum::庫存管理;
        });

        if (!$hasInventoryItems) {
            $this->error('此訂單中沒有需要庫存管理的商品');
            return 1;
        }

        if (!$this->confirm("確定要重新處理訂單 {$orderNumber} 的商品庫存嗎？")) {
            $this->info('操作已取消');
            return 0;
        }

        try {
            DB::beginTransaction();

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $product = $variant?->product;

                // 只處理需要庫存管理的商品
                if (!$variant || !$product || $product->inventory_management !== ProductInventoryManagementEnum::庫存管理) {
                    continue;
                }

                // 檢查是否已有庫存記錄
                $hasInventoryLog = InventoryLog::where('reference_type', get_class($item))
                    ->where('reference_id', $item->id)
                    ->where('type', InventoryLogTypeEnum::商品交易)
                    ->exists();

                if ($hasInventoryLog) {
                    $this->warn("商品 {$product->name} ({$variant->name}) 已有庫存記錄，跳過處理");
                    continue;
                }

                // 觸發庫存變更事件
                event(new InventoryChanged(
                    type: InventoryLogTypeEnum::商品交易,
                    variantId: $variant->id,
                    locationId: $order->shipping_location_id,
                    quantity: $item->quantity,
                    causer: $item->order->attributionUser,
                    reference: $item,
                    properties: $item->load('order:id,order_number')->toArray(),
                ));

                $this->info("已處理商品 {$product->name} ({$variant->name}) 的庫存");
            }

            DB::commit();

            $this->info("已成功重新處理訂單 {$orderNumber} 的商品庫存");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("處理失敗：" . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
