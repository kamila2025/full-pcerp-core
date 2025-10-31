<?php

namespace App\Listeners;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Events\InventoryChanged;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Location;
use App\Models\Variant;

class AdjustInventoryListener
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
    public function handle(InventoryChanged $event): void
    {
        if (!$event->variantId || !$event->locationId) return;

        $variant = Variant::find($event->variantId);

        $location = Location::find($event->locationId);

        $inventory = Inventory::firstOrCreate([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'location_id' => $location->id,
        ], ['quantity' => 0]);

        $before = $inventory->quantity;

        switch ($event->type) {
            case InventoryLogTypeEnum::商品建立:
            case InventoryLogTypeEnum::商品更新:
            case InventoryLogTypeEnum::庫存更新:
            case InventoryLogTypeEnum::批量商品更新:
            case InventoryLogTypeEnum::批量庫存更新:
            case InventoryLogTypeEnum::盤點單校正:
                $inventory->update(['quantity' => $event->quantity]);
                break;
            case InventoryLogTypeEnum::編輯商品交易:
                // 檢查是否存在對應的 InventoryLog 記錄
                $hasLog = InventoryLog::where('inventory_id', $inventory->id)
                    ->where('reference_type', get_class($event->reference))
                    ->where('reference_id', $event->reference->getKey())
                    ->exists();

                if ($hasLog) $inventory->increment('quantity', $event->quantity);

                else $inventory->decrement('quantity', $event->reference->quantity);
                break;
            case InventoryLogTypeEnum::刪除商品交易:
                // 檢查是否存在對應的 InventoryLog 記錄
                $hasLog = InventoryLog::where('inventory_id', $inventory->id)
                    ->where('reference_type', get_class($event->reference))
                    ->where('reference_id', $event->reference->getKey())
                    ->exists();

                // 如果沒有庫存變動記錄，則不進行庫存調整
                if (!$hasLog) return;

                // 如果有庫存變動記錄，則增加庫存
                else $inventory->increment('quantity', $event->quantity);
                break;
            case InventoryLogTypeEnum::規格刪除:
                $inventory->update(['quantity' => 0]);
                break;
            case InventoryLogTypeEnum::進貨單入庫:
            case InventoryLogTypeEnum::調撥單入庫:
                $inventory->increment('quantity', $event->quantity);
                break;
            case InventoryLogTypeEnum::商品交易:
            case InventoryLogTypeEnum::進貨單取消:
            case InventoryLogTypeEnum::退貨單出庫:
            case InventoryLogTypeEnum::調撥單移出:
                $inventory->decrement('quantity', $event->quantity);
                break;
            default:
                throw new \Exception("非法的庫存變動類型：{$event->type}");
        }

        // 只在數量真的有變化時才創建日誌
        if (!$inventory->wasChanged('quantity')) return;

        $inventory->refresh();

        InventoryLog::create([
            'inventory_id' => $inventory->id,
            'product_id' => $inventory->product->id,
            'variant_id' => $inventory->variant_id,
            'location_id' => $inventory->location_id,
            'type' => $event->type,
            'quantity_before' => $before,
            'quantity_after' => $inventory->quantity,
            'quantity_change' => $inventory->quantity - $before,
            'reason' => $event->reason,
            'causer_type' => $event->causer ? get_class($event->causer) : null,
            'causer_id' => $event->causer ? $event->causer->getKey() : null,
            'reference_type' => $event->reference ? get_class($event->reference) : null,
            'reference_id' => $event->reference ? $event->reference->getKey() : null,
            'properties' => $event->properties,
            'model_data' => [
                'variant' => $variant->toArray(),
                'location' => $location->toArray(),
            ],
        ]);
    }
}
