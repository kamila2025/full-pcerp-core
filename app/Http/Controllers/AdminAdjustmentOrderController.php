<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentOrder\AdjustmentOrderResultEnum;
use App\Enums\AdjustmentOrder\AdjustmentOrderStatusEnum;
use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Events\InventoryChanged;
use App\Models\AdjustmentOrder;
use App\Models\Location;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;

class AdminAdjustmentOrderController extends Controller
{
    public function __construct(protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                'user_id'                               => 'required|integer|exists:users,id',
                'location_id'                           => 'required|integer|exists:locations,id',
                'remark'                                => 'nullable|string',
                //
                'items'                                 => 'required|array',
                'items.*.variant_id'                    => 'nullable|integer|distinct|exists:variants,id',
                'items.*.product_name'                  => 'nullable|string',
                'items.*.variant_name'                  => 'nullable|string',
                'items.*.quantity'                      => 'required|integer|min:0',
            ])
            ->setMessages([
                //
            ])
            ->setAttributes([
                //   
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('Adjustment/Index', [
            'adjustments' => AdjustmentOrder::query()
                ->with('location', 'user')
                ->latest()
                ->paginate($attributes['per_page'] ?? null),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        return Inertia::render('Adjustment/CreateOrEdit', [
            'variants' => Inertia::lazy(fn () => Variant::query()
                ->with('product.categories', 'inventories')
                ->with(['product.brands' => fn ($query) => match (config('database.default')) {
                    'mysql' => $query->select('*', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(name, '$." . app()->getLocale() . "')) as name_display")),
                    'sqlite' => $query->select('*', DB::raw("json_extract(name, '$." . app()->getLocale() . "') as name_display")),
                }])
                ->withSum('inventories', 'quantity')
                ->join('products', 'variants.product_id', '=', 'products.id')
                ->orderBy('products.position')
                ->get()),
            'users' => User::all(),
            'locations' => Location::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        $record = DB::transaction(function () use ($attributes) {
            $order = AdjustmentOrder::query()->create([
                'user_id'               => $attributes['user_id'] ?? null,
                'location_id'           => $attributes['location_id'] ?? null,
                'order_number'          => date('YmdHis'),
                'remark'                => $attributes['remark'] ?? null,
                'start_at'              => now(),
                'status'                => AdjustmentOrderStatusEnum::盤點中,
            ]);

            foreach ($attributes['items'] as $item) {
                $variant = Variant::query()->findOrFail($item['variant_id']);

                $order->items()->create([
                    'variant_id' => $variant?->getKey() ?? null,
                    'product_name' => $variant->product?->name ?? $item['product_name'] ?? null,
                    'variant_name' => $variant->name ?? $item['variant_name'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order;
        });

        return redirect()->route('adjustment-orders.edit', $record->getKey());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        return Inertia::render('Adjustment/CreateOrEdit', [
            'adjustmentOrder' => AdjustmentOrder::query()->with('items.variant.inventories', 'user', 'location')->findOrFail($id),
            'variants' => Inertia::lazy(fn () => Variant::query()
                ->with('product.categories', 'inventories')
                ->with(['product.brands' => fn ($query) => match (config('database.default')) {
                    'mysql' => $query->select('*', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(name, '$." . app()->getLocale() . "')) as name_display")),
                    'sqlite' => $query->select('*', DB::raw("json_extract(name, '$." . app()->getLocale() . "') as name_display")),
                }])
                ->withSum('inventories', 'quantity')
                ->join('products', 'variants.product_id', '=', 'products.id')
                ->orderBy('products.position')
                ->get()),
            'users' => User::all(),
            'locations' => Location::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        $record = DB::transaction(function () use ($attributes, $id) {
            $order = AdjustmentOrder::query()->findOrFail($id);

            $order->items()->whereNotIn('id', array_filter(array_column($attributes['items'], 'id')))->delete();

            foreach ($attributes['items'] as $item) {
                $variant = Variant::query()->findOrFail($item['variant_id']);

                $order->items()->updateOrCreate(['id' => $item['id'] ?? null], [
                    'variant_id' => $variant?->getKey() ?? null,
                    'product_name' => $variant->product?->name ?? $item['product_name'] ?? null,
                    'variant_name' => $variant->name ?? $item['variant_name'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            $order->update([
                'user_id' => $attributes['user_id'] ?? null,
                'location_id' => $attributes['location_id'] ?? null,
                'remark' => $attributes['remark'] ?? null,
            ]);
            
            $order->touch();

            return $order;
        });

        return redirect()->route('adjustment-orders.edit', $record->getKey());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        DB::transaction(function () use ($id) {
            $adjustmentOrder = AdjustmentOrder::findOrFail($id);

            $adjustmentOrder->items()->delete();

            $adjustmentOrder->delete();
        });

        return redirect()->route('adjustment-orders.index');
    }

    public function status(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        $attributes = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(AdjustmentOrderStatusEnum::cases(), 'value')),
        ]);

        $record = DB::transaction(function () use ($attributes, $id) {
            $adjustmentOrder = AdjustmentOrder::with('items.variant.inventories')->findOrFail($id);

            if (!$adjustmentOrder->status->canFlowTo($attributes['status'])) {
                throw new \Exception("非法狀態轉換：{$adjustmentOrder->status->value} → {$attributes['status']}");
            }

            switch (AdjustmentOrderStatusEnum::from($attributes['status'])) {
                case AdjustmentOrderStatusEnum::已完成:
                    // 計算每個項目的差異數量
                    foreach ($adjustmentOrder->items as $item) {
                        $systemQuantity = $item->variant->inventories->where('location_id', $adjustmentOrder->location_id)->sum('quantity') ?? 0;

                        $item->update([
                            'original_quantity' => $systemQuantity,
                            'difference_quantity' => $item->quantity - $systemQuantity
                        ]);
                    }

                    $adjustmentOrder->updateQuietly([
                        'result' => $adjustmentOrder->items->sum('difference_quantity') === 0
                            ? AdjustmentOrderResultEnum::符合
                            : AdjustmentOrderResultEnum::不符合,
                        'status' => AdjustmentOrderStatusEnum::已完成,
                        'end_at' => now(),
                    ]);
                    break;

                default:
                    throw new \Exception("無法轉換狀態: {$adjustmentOrder->status->value} → {$attributes['status']}");
            }

            return $adjustmentOrder;
        });

        return redirect()->route('adjustment-orders.edit', $record->getKey());
    }

    public function corrected(string $id)
    {
        Gate::authorize(PermissionNameEnum::盤點管理);

        DB::transaction(function () use ($id) {
            $adjustmentOrder = AdjustmentOrder::with('items.variant.product')->findOrFail($id);

            if (!$adjustmentOrder->result->canFlowTo(AdjustmentOrderResultEnum::已校正)) {
                throw new \Exception("非法狀態轉換：{$adjustmentOrder->result->value} → " . AdjustmentOrderResultEnum::已校正->value);
            }

            foreach ($adjustmentOrder->items as $orderItem) {
                event(new InventoryChanged(
                    type: InventoryLogTypeEnum::盤點單校正,
                    variantId: $orderItem?->variant_id,
                    locationId: $adjustmentOrder?->location_id,
                    quantity: $orderItem->quantity,
                    causer: auth()->user(),
                    reference: $orderItem,
                    properties: $orderItem->load('adjustmentOrder:id,order_number')->toArray(),
                ));
            }

            $adjustmentOrder->updateQuietly([
                'corrected_at' => now(),
                'result' => AdjustmentOrderResultEnum::已校正,
            ]);
        });

        return redirect()->route('adjustment-orders.index');
    }
}
