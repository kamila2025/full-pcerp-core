<?php

namespace App\Http\Controllers;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AdminProductAnalyticsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::銷售報告);

        $attributes = $request->validate([
            'start_date'            => 'nullable',
            'end_date'              => 'nullable',
            'attribution_user_id'   => 'nullable|string',
            'location_id'           => 'nullable|string',
            'product_name'          => 'nullable|string',
        ]);

        $startDate = isset($attributes['start_date']) ? Carbon::createFromTimestamp($attributes['start_date']) : now('Asia/Taipei')->startOfMonth()->setTimezone(config('app.timezone'));

        $endDate = isset($attributes['end_date']) ? Carbon::createFromTimestamp($attributes['end_date']) : now('Asia/Taipei')->endOfDay()->setTimezone(config('app.timezone'));

        $attributionUserId = isset($attributes['attribution_user_id']) ? explode(',', $attributes['attribution_user_id']) : null;

        $locationId = isset($attributes['location_id']) ? explode(',', $attributes['location_id']) : null;

        $orders = Order::query()
            ->select(
                'products.name as product_name',
                DB::raw('COUNT(*) as nuit_sold'),
                DB::raw('SUM(order_items.total_amount) as total_sales'),
                DB::raw('SUM(order_items.quantity * order_items.cost_price) as total_cost'),
                DB::raw('SUM(order_items.cost_price) as total_cost_price'),
                DB::raw('SUM(order_items.total_amount) - SUM(order_items.quantity * order_items.cost_price) as gross_profit'),
                DB::raw('ROUND((SUM(order_items.total_amount) - SUM(order_items.quantity * order_items.cost_price)) / NULLIF(SUM(order_items.total_amount), 0) * 100, 2) as gross_margin'),
            )
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereTimestampBetween('orders.created_at', $startDate->timestamp, $endDate->timestamp)
            ->when(isset($attributionUserId), fn ($query) => $query->whereIn('orders.attribution_user_id', $attributionUserId))
            ->when(isset($locationId), fn ($query) => $query->whereIn('orders.location_id', $locationId))
            ->when(isset($attributes['product_name']), fn ($query) => $query->where(function ($query) use ($attributes) {
                $query
                    ->where('products.name', 'like', '%' . $attributes['product_name'] . '%')
                    ->orWhere('order_items.product_name', 'like', '%' . $attributes['product_name'] . '%');
            }))
            ->whereIn('orders.status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->groupBy('products.name')
            ->orderBy('gross_profit', 'desc')
            ->paginate($attributes['per_page'] ?? null);

        return Inertia::render('Analytics/Product', [
            'users' => User::query()->pluck('name', 'id'),
            'locations' => Location::query()->pluck('name', 'id'),
            'orders' => $orders,
            //
            'start_date' => $startDate->timestamp,
            'end_date' => $endDate->timestamp,
        ]);
    }
}
