<?php

namespace App\Console\Commands\UpdatePatch;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class PatchInventoryLogsStructure extends Command
{
    protected $signature = 'patch:inventory-logs';

    protected $description = '補丁：修改 inventory_logs 表結構（取消 FK、設 nullable、新增 model_data 欄位）';

    public function handle()
    {
        $this->info('🔍 開始補丁 inventory_logs...');

        // Step 1: 移除外鍵
        $this->info('🔁 移除 location_id 與 variant_id 外鍵...');

        try {
            Schema::table('inventory_logs', function (Blueprint $table) {
                // SQLite 不支援 dropForeign，改用 try-catch
                try {
                    $table->dropForeign('inventory_logs_variant_id_foreign');
                    $table->dropForeign('inventory_logs_location_id_foreign');
                    $table->dropIndex('inventory_logs_location_id_foreign');
                    $this->info('✅ 已移除 location_id 與 variant_id 外鍵。');
                } catch (\Throwable $e) {
                    $this->warn('⚠️ location_id 與 variant_id 外鍵無法移除（可能已不存在）：' . $e->getMessage());
                }
            });

            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('variant_id')->comment('規格')->change();
                $table->unsignedBigInteger('location_id')->comment('倉庫')->change();
            });

            $this->info('✅ location_id 與 variant_id 並加註解。');
        } catch (\Throwable $e) {
            $this->error('❌ 無法移除 location_id 與 variant_id 外鍵：' . $e->getMessage());
        }

        // Step 2: 新增 model_data 欄位
        if (!Schema::hasColumn('inventory_logs', 'model_data')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->longText('model_data')->nullable()->comment('異動資料 model');
            });
            $this->info('✅ 新增 model_data 欄位完成。');
        } else {
            $this->info('✅ model_data 欄位已存在，略過新增。');
        }

        $this->info('🎉 補丁完成！');
        return self::SUCCESS;
    }
}
