<?php

namespace App\Models;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => InventoryLogTypeEnum::class,
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'quantity_change' => 'integer',
        'properties' => 'array',
        'model_data' => 'array',
    ];

    /**
     * 關聯到庫存
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * 關聯到商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 關聯到變體
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * 關聯到倉庫位置
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * 關聯到操作者
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id')->withTrashed();
    }

    /**
     * 關聯到參考
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 計算指定商品和倉庫的正確庫存數量
     *
     * @param int $variantId 商品變體ID
     * @param int $locationId 倉庫ID
     * @param string|null $referenceType 參考類型（可選）
     * @param int|null $referenceId 參考ID（可選）
     * @return array 返回計算結果，包含正確庫存數量和相關記錄
     */
    public static function calculateCorrectInventory(int $variantId, int $locationId, ?string $referenceType = null, ?int $referenceId = null): array
    {
        $query = self::query()
            ->where('variant_id', $variantId)
            ->where('location_id', $locationId)
            ->orderBy('created_at')
            ->orderBy('id');

        // 如果指定了參考資訊，則只查詢相關記錄
        if ($referenceType && $referenceId) {
            $query->where('reference_type', $referenceType)
                  ->where('reference_id', $referenceId);
        }

        $logs = $query->get();

        $correctQuantity = 0;
        $details = [];

        foreach ($logs as $log) {
            $change = $log->quantity_change;

            // 根據不同類型的操作來計算庫存
            switch ($log->type) {
                case InventoryLogTypeEnum::商品建立:
                case InventoryLogTypeEnum::商品更新:
                case InventoryLogTypeEnum::庫存更新:
                case InventoryLogTypeEnum::批量商品更新:
                case InventoryLogTypeEnum::批量庫存更新:
                case InventoryLogTypeEnum::盤點單校正:
                    $correctQuantity = $log->quantity_after;
                    break;

                case InventoryLogTypeEnum::進貨單入庫:
                case InventoryLogTypeEnum::調撥單入庫:
                    $correctQuantity += $change;
                    break;

                case InventoryLogTypeEnum::商品交易:
                case InventoryLogTypeEnum::進貨單取消:
                case InventoryLogTypeEnum::退貨單出庫:
                case InventoryLogTypeEnum::調撥單移出:
                    $correctQuantity -= abs($change);
                    break;

                case InventoryLogTypeEnum::編輯商品交易:
                case InventoryLogTypeEnum::刪除商品交易:
                    $correctQuantity += $change;
                    break;
            }

            $details[] = [
                'id' => $log->id,
                'type' => $log->type,
                'created_at' => $log->created_at,
                'quantity_before' => $log->quantity_before,
                'quantity_after' => $log->quantity_after,
                'quantity_change' => $change,
                'calculated_quantity' => $correctQuantity,
                'reference_type' => $log->reference_type,
                'reference_id' => $log->reference_id,
            ];
        }

        return [
            'correct_quantity' => $correctQuantity,
            'current_quantity' => $logs->last()?->quantity_after ?? 0,
            'difference' => ($logs->last()?->quantity_after ?? 0) - $correctQuantity,
            'details' => $details,
        ];
    }
}