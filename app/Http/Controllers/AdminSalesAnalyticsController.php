<?php

namespace App\Http\Controllers;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Exports\SalesAnalysisExport;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AdminSalesAnalyticsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::銷售報告);

        $attributes = $request->validate([
            'start_date'    => 'nullable',
            'end_date'      => 'nullable',
        ]);

        $startDate = isset($attributes['start_date']) ? Carbon::createFromTimestamp($attributes['start_date']) : now('Asia/Taipei')->startOfMonth()->setTimezone(config('app.timezone'));

        $endDate = isset($attributes['end_date']) ? Carbon::createFromTimestamp($attributes['end_date']) : now('Asia/Taipei')->endOfDay()->setTimezone(config('app.timezone'));

        $sales = Order::query()
            ->join('users', 'orders.attribution_user_id', '=', 'users.id')
            ->leftJoin(DB::raw('(SELECT order_id, SUM(cost_price * quantity) as total_cost FROM order_items GROUP BY order_id) as order_items'), 'orders.id', '=', 'order_items.order_id')
            ->leftJoin(DB::raw('(SELECT order_id, SUM(fee) as total_fee FROM transactions GROUP BY order_id) as transactions'), 'orders.id', '=', 'transactions.order_id')
            ->whereTimestampBetween('orders.created_at', $startDate->timestamp, $endDate->timestamp)
            ->whereIn('orders.status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->select(
                'users.name as name',
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('SUM(orders.amount) as total_amount'),
                DB::raw('COALESCE(SUM(order_items.total_cost), 0) as total_cost_price'),
                DB::raw('SUM(orders.total_shipping) as total_shipping'),
                DB::raw('COALESCE(SUM(transactions.total_fee), 0) as total_fee'),
                DB::raw('COALESCE(SUM(orders.total_tax), 0) as total_tax'),
                // DB::raw("(SELECT SUM(amount) FROM orders WHERE UNIX_TIMESTAMP(created_at) BETWEEN {$startDate->timestamp} AND {$endDate->timestamp}) as grand_total_amount"),
                // DB::raw("(SELECT COUNT(DISTINCT id) FROM orders WHERE UNIX_TIMESTAMP(created_at) BETWEEN {$startDate->timestamp} AND {$endDate->timestamp}) as grand_total_orders")
            )
            ->selectSub(Order::query()->selectRaw('SUM(amount)')->whereTimestampBetween('created_at', $startDate->timestamp, $endDate->timestamp), 'grand_total_amount')
            ->selectSub(Order::query()->selectRaw('COUNT(DISTINCT id)')->whereTimestampBetween('created_at', $startDate->timestamp, $endDate->timestamp), 'grand_total_orders')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.id')
            ->paginate($attributes['per_page'] ?? null);

        return Inertia::render('Analytics/Sales', [
            'filters' => ['start_date' => $startDate->timestamp, 'end_date' => $endDate->timestamp],
            'sales' => $sales,
        ]);
    }

    public function export(Request $request)
    {
        Gate::authorize(PermissionNameEnum::銷售報告);

        $attributes = $request->validate([
            'start_date'    => 'nullable',
            'end_date'      => 'nullable',
        ]);

        $startDate = isset($attributes['start_date']) ? Carbon::createFromTimestamp($attributes['start_date']) : now('Asia/Taipei')->startOfMonth()->setTimezone(config('app.timezone'));

        $endDate = isset($attributes['end_date']) ? Carbon::createFromTimestamp($attributes['end_date']) : now('Asia/Taipei')->endOfDay()->setTimezone(config('app.timezone'));

        $filename = "銷售員分析_{$startDate->format('Ymd')}_{$endDate->format('Ymd')}.xlsx";

        return Excel::download(new SalesAnalysisExport($startDate, $endDate), $filename);
    }
}
