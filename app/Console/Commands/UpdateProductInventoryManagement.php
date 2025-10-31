<?php

namespace App\Console\Commands;

use App\Enums\Product\ProductInventoryManagementEnum;
use App\Models\Product;
use Illuminate\Console\Command;

class UpdateProductInventoryManagement extends Command
{
    protected $signature = 'product:update-inventory-management {--type= : The inventory management type}';

    protected $description = '批量更新產品的庫存追蹤狀態';

    public function handle()
    {
        $type = $this->option('type');
        
        if (empty($type)) {
            $choices = collect(ProductInventoryManagementEnum::cases())
                ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                ->all();

            $type = $this->choice(
                '請選擇庫存管理類型',
                $choices
            );
        }

        $validTypes = array_map(fn ($case) => $case->value, ProductInventoryManagementEnum::cases());
        
        if (!in_array($type, $validTypes)) {
            $this->error('無效的庫存管理類型。請使用: ' . implode(', ', $validTypes));
            return 1;
        }

        $inventoryManagement = ProductInventoryManagementEnum::from($type);

        if (!$this->confirm("確定要將所有產品更新為 {$inventoryManagement->name} 嗎？")) {
            $this->info('操作已取消');
            return 0;
        }

        $count = Product::query()->where('inventory_management', '!=', $inventoryManagement)->update([
            'inventory_management' => $inventoryManagement
        ]);

        $this->info("已成功將 {$count} 個產品更新為 {$inventoryManagement->name}");
        
        return 0;
    }
} 