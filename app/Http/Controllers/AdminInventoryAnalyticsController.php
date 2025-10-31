<?php

namespace App\Http\Controllers;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Models\Inventory;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AdminInventoryAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::庫存分析);

        $perPage = $request->input('per_page', 50);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // $startDate = $request->input('start_date', now()->subMonth());

        // $endDate = $request->input('end_date', now());

        $baseQuery = Inventory::query()
            ->select(
                'inventories.variant_id',
                'inventories.product_id',
                DB::raw('SUM(inventories.quantity) as current_stock'),
                'products.name as product_name',
                'variants.id as existing_variant_id',
                'variants.name as variant_name',
                'variants.sku'
            )
            ->whereRelation('product', 'products.inventory_management', ProductInventoryManagementEnum::庫存管理)
            ->leftJoin('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('variants', 'inventories.variant_id', '=', 'variants.id')
            ->groupBy(
                'inventories.variant_id',
                'inventories.product_id',
                'products.name',
                'variants.id',
                'variants.name',
                'variants.sku'
            );

        $rawResults = $baseQuery->get();
        $variantIds = $rawResults->pluck('variant_id')->toArray();

        $logs = InventoryLog::query()
            ->whereIn('variant_id', $variantIds)
            // ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('variant_id');

        $items = $rawResults->map(function ($item) use ($logs) {
            $variantLogs = $logs[$item->variant_id] ?? collect();

            if (is_null($item->existing_variant_id)) {
                $lastLog = $variantLogs->last();
                if ($lastLog && isset($lastLog->model_data['variant'])) {
                    $item->variant_name = '已刪除規格';
                    $item->sku = $lastLog->model_data['variant']['sku'] ?? 'N/A';
                }
            }

            $sumByType = fn (array $types, $sign = null) => $variantLogs
                ->whereIn('type', (array) $types)
                ->when($sign, fn ($q) => $q->filter(fn ($log) => $sign === '>' ? $log->quantity_change > 0 : $log->quantity_change < 0))
                ->sum(fn ($log) => abs($log->quantity_change));

            $item->purchase_quantity        = $sumByType([InventoryLogTypeEnum::進貨單入庫]);
            $item->sales_quantity           = $sumByType([InventoryLogTypeEnum::商品交易, InventoryLogTypeEnum::編輯商品交易]);
            $item->order_return_quantity    = $sumByType([InventoryLogTypeEnum::刪除商品交易]);
            $item->purchase_return_quantity = $sumByType([InventoryLogTypeEnum::進貨單取消]);
            $item->adjustment_in_quantity   = $sumByType([InventoryLogTypeEnum::盤點單校正, InventoryLogTypeEnum::庫存更新, InventoryLogTypeEnum::商品更新], '>');
            $item->adjustment_out_quantity  = $sumByType([InventoryLogTypeEnum::盤點單校正, InventoryLogTypeEnum::庫存更新, InventoryLogTypeEnum::商品更新, InventoryLogTypeEnum::規格刪除], '<');
            $item->transfer_in_quantity     = $sumByType([InventoryLogTypeEnum::調撥單入庫]);
            $item->transfer_out_quantity    = $sumByType([InventoryLogTypeEnum::調撥單移出]);

            return $item;
        });

        $items = $items->sortBy([
            ['product_name', 'asc'],
            [fn ($item) => $item->variant_name === '已刪除規格' ? 'z' . ($item->sku ?? '') : $item->variant_name ?? '', 'asc']
        ])->values();

        $total = $items->count();

        $slicedItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $summary = (object)[
            'variant_name' => '全部商品',
            'sku' => '-',
            'purchase_quantity' => $items->sum('purchase_quantity'),
            'sales_quantity' => $items->sum('sales_quantity'),
            'order_return_quantity' => $items->sum('order_return_quantity'),
            'purchase_return_quantity' => $items->sum('purchase_return_quantity'),
            'adjustment_in_quantity' => $items->sum('adjustment_in_quantity'),
            'adjustment_out_quantity' => $items->sum('adjustment_out_quantity'),
            'transfer_in_quantity' => $items->sum('transfer_in_quantity'),
            'transfer_out_quantity' => $items->sum('transfer_out_quantity'),
            'current_stock' => $items->sum('current_stock'),
            '_is_summary' => true,
        ];

        $paginated = new LengthAwarePaginator(
            collect([$summary])->merge($slicedItems),
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return Inertia::render('Analytics/Inventory', [
            'inventories' => $paginated,
        ]);
    }
}
