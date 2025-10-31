<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class CleanOrphanedActivityLogs extends Command
{
    protected $signature = 'clean:orphaned-activity-logs {--force : 強制執行不需確認}';
    
    protected $description = '清理所有未刪除的活動記錄';

    public function handle()
    {
        $this->info('開始清理未刪除的活動記錄...');

        // 取得所有不同類型的活動記錄
        $activityTypes = Activity::distinct()->pluck('subject_type');

        $allOrphanedLogs = collect();
        
        foreach ($activityTypes as $type) {
            if (empty($type)) continue;

            $this->info("檢查 {$type} 相關的活動記錄...");
            
            // 取得該類型的所有活動記錄
            $activities = Activity::where('subject_type', $type)
                ->whereNotNull('subject_id')
                ->get();

            if ($activities->isEmpty()) {
                continue;
            }

            // 檢查每個記錄的對應資料是否存在
            foreach ($activities as $activity) {
                // 檢查資料表是否存在
                $tableName = (new $type)->getTable();
                if (!DB::table($tableName)->where('id', $activity->subject_id)->exists()) {
                    $allOrphanedLogs->push($activity);
                }
            }
        }

        // 顯示要刪除的記錄
        if ($allOrphanedLogs->isNotEmpty()) {
            $this->warn('將要刪除的活動記錄:');
            // 按類型分組顯示
            $groupedLogs = $allOrphanedLogs->groupBy('subject_type');
            
            foreach ($groupedLogs as $type => $logs) {
                $this->info("\n{$type} 的活動記錄:");
                $this->table(
                    ['ID', '資料ID', '描述', '建立時間'],
                    $logs->map(fn($log) => [
                        $log->id,
                        $log->subject_id,
                        $log->description,
                        $log->created_at
                    ])
                );
            }
        }

        if ($allOrphanedLogs->isEmpty()) {
            $this->info('沒有需要清理的活動記錄');
            return 0;
        }

        // 確認是否要刪除
        if (!$this->option('force') && !$this->confirm('確定要刪除這些活動記錄嗎？')) {
            $this->info('取消刪除操作');
            return 0;
        }

        // 執行刪除
        $count = Activity::whereIn('id', $allOrphanedLogs->pluck('id'))->delete();
        
        $this->info("清理完成，總共刪除了 {$count} 筆記錄");
        return 0;
    }
} 