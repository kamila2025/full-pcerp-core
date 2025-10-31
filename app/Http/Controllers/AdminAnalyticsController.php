<?php

namespace App\Http\Controllers;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AdminAnalyticsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Gate::authorize(PermissionNameEnum::銷售報告);

        $attributes = $request->validate([
            'start_date'    => 'nullable',
            'end_date'      => 'nullable',
        ]);

        $startDate = isset($attributes['start_date']) ? Carbon::createFromTimestamp($attributes['start_date']) : now('Asia/Taipei')->startOfMonth()->setTimezone(config('app.timezone'));

        $endDate = isset($attributes['end_date']) ? Carbon::createFromTimestamp($attributes['end_date']) : now('Asia/Taipei')->endOfDay()->setTimezone(config('app.timezone'));

        $orderQuery = Order::query()
            ->whereTimestampBetween('orders.created_at', $startDate->timestamp, $endDate->timestamp)
            ->whereIn('orders.status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->newQuery();

        $orders = (clone $orderQuery)
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get();

        $products = (clone $orderQuery)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_items.total_amount) as total_amount'))
            ->groupBy('products.name')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();

        $customers = (clone $orderQuery)
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select('customers.additional->customer_source as customer_source', DB::raw('SUM(orders.amount) as orders_sum_amount'))
            ->groupBy('customers.additional->customer_source')
            ->orderBy('orders_sum_amount', 'desc')
            ->get();

        $transactions = (clone $orderQuery)
            ->join('transactions', 'orders.id', '=', 'transactions.order_id')
            ->select('transactions.gateway_title', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('transactions.gateway_title')
            ->orderBy('total_amount', 'desc')
            ->get();

        $locations = (clone $orderQuery)
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->select('locations.name', DB::raw('SUM(orders.amount) as total_amount'))
            ->groupBy('locations.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        $users = (clone $orderQuery)
            ->join('users', 'orders.attribution_user_id', '=', 'users.id')
            ->select('users.name', DB::raw('SUM(orders.amount) as total_amount'))
            ->groupBy('users.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        return Inertia::render('Analytics/Index', [
            'filters' => ['start_date' => $startDate->timestamp, 'end_date' => $endDate->timestamp],
            'orders' => $orders,
            'products' => $products,
            'customers' => $customers,
            'transactions' => $transactions,
            'locations' => $locations,
            'users' => $users,
        ]);
    }
}
