<?php

namespace App\Services;

use App\Models\Product;
use App\Events\InventoryChanged;
use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Models\VariantValue;
use App\Models\VariantType;

class ProductService
{
    protected function deleteUnusedVariants(array $keepVariantIds, int|Product $productId)
    {
        $product = $productId instanceof Product ? $productId : Product::query()->findOrFail($productId);

        // 取得要被刪除的變體
        $obsoleteVariants = $product->variants()->whereNotIn('id', $keepVariantIds);

        // 對每個要刪除的變體，記錄其庫存變化
        $obsoleteVariants->each(function ($variant) {
            foreach ($variant->inventories as $variantInventory) {
                event(new InventoryChanged(
                    type: InventoryLogTypeEnum::規格刪除,
                    variantId: $variant->id,
                    locationId: $variantInventory->location_id,
                    quantity: $variantInventory->quantity,
                    causer: auth()->user(),
                ));
            }

            $variant->delete();
        });
    }

    public function updateVariants(array $variants = [], int|Product $productId, InventoryLogTypeEnum $inventoryType = null)
    {
        $product = $productId instanceof Product ? $productId : Product::query()->findOrFail($productId);

        // 刪除未使用的變體
        $this->deleteUnusedVariants(array_filter(array_column($variants, 'id')), $product);

        // 取得變體選項
        $typeIds = $product->types()->pluck('id');

        if ($typeIds->count() === 0 && count($variants) > 1) throw new \Exception('商品尚未設定規格選項');

        // 更新變體
        foreach ($variants as $variantIndex => $variant) {
            $variantValues = VariantValue::whereIn('variant_type_id', $typeIds)
                ->whereIn('name', array_column($variant['values'] ?? [], 'name'))
                ->orderBy(VariantType::select('position')->whereColumn('variant_types.id', 'variant_values.variant_type_id'))
                ->get();

            if ($typeIds->count() !== $variantValues->count()) throw new \Exception('商品規格選項數量不符');

            $modelVariant = $product->variants()->updateOrCreate(['id' => $variant['id'] ?? null], [
                'sku'               => $variant['sku'] ?? null,
                'barcode'           => $variant['barcode'] ?? null,
                'options'           => $variantValues->pluck('id')->join(', ') ?: null,
                'name'              => $variantValues->pluck('name')->join(', ') ?: null,
                'price'             => $variant['price'],
                'compare_at_price'  => $variant['compare_at_price'] ?? 0,
                'cost_price'        => $variant['cost_price'] ?? 0,
                'position'          => $variantIndex,
                'status'            => $variant['status'],
            ]);

            $modelVariant->values()->sync($variantValues->pluck('id'));

            if ($product->inventory_management === ProductInventoryManagementEnum::庫存管理) {
                foreach ($variant['inventories'] as $inventory) {
                    event(new InventoryChanged(
                        type: $inventoryType ?? $modelVariant->wasRecentlyCreated ? InventoryLogTypeEnum::商品建立 : InventoryLogTypeEnum::商品更新,
                        variantId: $modelVariant->id,
                        locationId: $inventory['location_id'],
                        quantity: $inventory['quantity'],
                        causer: auth()->user(),
                    ));
                }
            }
        }
    }

    public function updateTypes(array $types = [], int|Product $productId)
    {
        $product = $productId instanceof Product ? $productId : Product::query()->findOrFail($productId);

        $product->types()->whereNotIn('name', array_column($types, 'name'))->delete();

        foreach ($types as $typeIndex => $type) {
            $productType = $product->types()->updateOrCreate(['name' => $type['name'] ?? null], [
                'position' => $typeIndex,
            ]);

            $productType->values()->whereNotIn('name', array_column($type['values'], 'name'))->delete();

            foreach ($type['values'] as $optionIndex => $option) {
                $productType->values()->updateOrCreate(['name' => $option['name'] ?? null], [
                    'position' => $optionIndex,
                ]);
            }
        }
    }
}
