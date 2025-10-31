<?php

namespace App\Http\Controllers;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AdminOrderAnalyticsController extends Controller
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

        $orders = Order::query()
            ->with('customer', 'attributionUser')
            ->withSum('items', 'cost_price')
            ->withSum('transactions', 'fee')
            ->whereTimestampBetween('orders.created_at', $startDate->timestamp, $endDate->timestamp)
            ->whereIn('orders.status', [OrderStatusEnum::開啟, OrderStatusEnum::封存])
            ->paginate($attributes['per_page'] ?? null);

        return Inertia::render('Analytics/Order', [
            'filters' => ['start_date' => $startDate->timestamp, 'end_date' => $endDate->timestamp],
            'orders' => $orders,
        ]);
    }
}
