<?php

namespace App\Http\Controllers;

use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Order\OrderFulfillmentStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // 計算待出貨訂單數量
        $orderToShip = Order::query()
            ->whereIn('fulfillment_status', [
                OrderFulfillmentStatusEnum::未出貨,
                OrderFulfillmentStatusEnum::部分出貨,
            ])
            ->whereIn('status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->count();

        // 計算未付款訂單數量
        $orderUnpaid = Order::query()
            ->whereIn('financial_status', [
                OrderFinancialStatusEnum::未付款,
                OrderFinancialStatusEnum::部分付款,
            ])
            ->whereIn('status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->count();

        // 計算庫存不足的產品數量
        $productLowStock = Variant::query()
            ->whereRelation('product', 'inventory_management', ProductInventoryManagementEnum::庫存管理)
            ->whereRelation('inventories', 'quantity', '<', 0)
            ->count();

        return Inertia::render('Dashboard', [
            'orderToShip' => $orderToShip,
            'orderUnpaid' => $orderUnpaid,
            'productLowStock' => $productLowStock,
        ]);
    }
}
