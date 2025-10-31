<?php

namespace App\Console\Commands;

use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateOrderStatusCommand extends Command
{
    protected $signature = 'order:update-status
                          {--from= : 開始日期 (Y-m-d)}
                          {--to= : 結束日期 (Y-m-d)}
                          {--status= : 新狀態 (draft:草稿/open:開啟/cancelled:取消/archived:封存/deleted:刪除)}';

    protected $description = '批量更新指定日期區間內的訂單狀態';

    public function handle()
    {
        // 驗證日期區間
        $fromDate = $this->option('from');
        $toDate = $this->option('to');
        $status = $this->option('status');

        if (!$fromDate || !$toDate || !$status) {
            $this->error('請提供所有必要的選項: --from (開始日期), --to (結束日期), 和 --status (狀態)');
            return 1;
        }

        try {
            $fromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
            $toDate = Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay();
        } catch (\Exception $e) {
            $this->error('日期格式無效。請使用 Y-m-d 格式 (例如: 2024-03-25)');
            return 1;
        }

        // 驗證狀態
        try {
            $newStatus = OrderStatusEnum::from($status);
        } catch (\Exception $e) {
            $this->error('狀態無效。可用選項: draft (草稿), open (開啟), cancelled (取消), archived (封存), deleted (刪除)');
            return 1;
        }

        // 取得日期區間內的訂單
        $orders = Order::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where('status', '!=', $newStatus)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('在指定的日期區間內沒有找到需要更新的訂單。');
            return 0;
        }

        // 向用戶確認
        if (!$this->confirm("這將會更新 {$orders->count()} 筆訂單的狀態為 '{$status}'。是否要繼續？")) {
            return 0;
        }

        // 更新訂單
        $updatedCount = 0;
        foreach ($orders as $order) {
            try {
                $order->status = $newStatus;
                $order->save();
                $updatedCount++;
            } catch (\Exception $e) {
                $this->error("更新訂單 #{$order->id} 失敗: {$e->getMessage()}");
            }
        }

        $this->info("成功更新 {$updatedCount} 筆訂單的狀態為 '{$status}'。");
        return 0;
    }
}
