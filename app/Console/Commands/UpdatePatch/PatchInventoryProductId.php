<?php

namespace App\Console\Commands\UpdatePatch;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatchInventoryProductId extends Command
{
    protected $signature = 'patch:inventories';

    protected $description = '補丁：新增 inventories.product_id 欄位，並從 variants 表補資料並建立外鍵';

    public function handle()
    {
        // Step 1: 新增欄位（若尚未存在）
        if (!Schema::hasColumn('inventories', 'product_id')) {
            $this->info('🔧 新增 product_id 欄位至 inventories 表...');
            Schema::table('inventories', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->cascadeOnDelete()->comment('商品');
                $table->unsignedBigInteger('variant_id')->comment('規格')->change();
                $table->unsignedBigInteger('location_id')->comment('倉庫')->change();
                $table->dropForeign('inventories_variant_id_foreign');
                $table->dropForeign('inventories_location_id_foreign');
                $table->dropIndex('inventories_variant_id_foreign');
                $table->dropIndex('inventories_location_id_foreign');
            });
        } else {
            $this->info('✅ inventories 表已有 product_id 欄位，跳過新增。');
        }

        // Step 2: 自動補上 product_id（從 variants 表查出來）
        $this->info('🔄 從 variants 表補上 inventories 的 product_id...');

        $inventories = DB::table('inventories')->whereNull('product_id')->get(['id', 'variant_id']);

        $counter = 0;

        foreach ($inventories as $inv) {
            $productId = DB::table('variants')->where('id', $inv->variant_id)->value('product_id');
            if ($productId) {
                DB::table('inventories')->where('id', $inv->id)->update(['product_id' => $productId]);
                $counter++;
            }
        }

        $this->info("✅ 補上 $counter 筆 product_id。");

        // Step 3: 檢查是否還有 NULL
        $missing = DB::table('inventories')->whereNull('product_id')->count();
        if ($missing > 0) {
            $this->error("❌ 還有 $missing 筆資料無法補上 product_id，請手動處理。外鍵尚未建立。");
            return self::FAILURE;
        }

        // Step 4: 要把 product_id 欄位改成 not null
        $this->info('🔐 嘗試把 product_id 欄位改成 not null...');
        try {
            Schema::table('inventories', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable(false)->comment('商品')->change();
            });
            $this->info('✅ 欄位改成 not null 成功。');
        } catch (\Throwable $e) {
            $this->warn('⚠️ 欄位改成 not null 失敗。錯誤訊息：' . $e->getMessage());
        }

        $this->info('🎉 補丁完成！');
        return self::SUCCESS;
    }
}
