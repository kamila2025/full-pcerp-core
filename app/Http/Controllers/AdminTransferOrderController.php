<?php

namespace App\Http\Controllers;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\Product\ProductInventoryManagementEnum;
use App\Enums\TransferOrder\TransferOrderArrivalStatusEnum;
use App\Enums\TransferOrder\TransferOrderStatusEnum;
use App\Events\InventoryChanged;
use App\Models\AdjustmentOrder;
use App\Models\InventoryAdjustment;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;

class AdminTransferOrderController extends Controller
{
    public function __construct(protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                'transfer_user_id'                      => 'required|integer|exists:users,id',
                'receiver_user_id'                      => 'nullable|integer|exists:users,id',
                'from_location_id'                      => 'required|integer|exists:locations,id',
                'to_location_id'                        => 'required|integer|exists:locations,id|different:from_location_id',
                'delivery_date'                         => 'nullable|date',
                'remark'                                => 'nullable|string',
                // 
                'items'                                 => 'required|array',
                'items.*.variant_id'                    => 'nullable|integer|distinct|exists:variants,id',
                'items.*.product_name'                  => 'nullable|string',
                'items.*.variant_name'                  => 'nullable|string',
                'items.*.quantity'                      => 'required|integer|min:1',
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
        Gate::authorize(PermissionNameEnum::調撥管理);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('Transfer/Index', [
            'transferOrders' => TransferOrder::query()
                ->with('fromLocation', 'toLocation', 'transferUser', 'receiverUser')
                ->latest()
                ->paginate($attributes['per_page'] ?? null),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Gate::authorize(PermissionNameEnum::調撥管理);

        return Inertia::render('Transfer/CreateOrEdit', [
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
        Gate::authorize(PermissionNameEnum::調撥管理);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        $record = DB::transaction(function () use ($attributes) {
            $order = TransferOrder::query()->create([
                'transfer_user_id'      => $attributes['transfer_user_id'] ?? null,
                'receiver_user_id'      => $attributes['receiver_user_id'] ?? null,
                'from_location_id'      => $attributes['from_location_id'] ?? null,
                'to_location_id'        => $attributes['to_location_id'] ?? null,
                'order_number'          => date('YmdHis'),
                'delivery_date'         => $attributes['delivery_date'] ?? null,
                'remark'                => $attributes['remark'] ?? null,
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

        return redirect()->route('transfer-orders.edit', $record->getKey());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize(PermissionNameEnum::調撥管理);

        return Inertia::render('Transfer/CreateOrEdit', [
            'transferOrder' => TransferOrder::query()->with('items.variant.inventories', 'transferUser', 'receiverUser', 'fromLocation', 'toLocation')->findOrFail($id),
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
        Gate::authorize(PermissionNameEnum::調撥管理);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes, $id) {
            $order = TransferOrder::query()->findOrFail($id);

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
                'transfer_user_id'      => $attributes['transfer_user_id'] ?? null,
                'receiver_user_id'      => $attributes['receiver_user_id'] ?? null,
                'from_location_id'      => $attributes['from_location_id'] ?? null,
                'to_location_id'        => $attributes['to_location_id'] ?? null,
                'delivery_date'         => $attributes['delivery_date'] ?? null,
            ]);

            $order->touch();
        });

        return redirect()->route('transfer-orders.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::調撥管理);

        DB::transaction(function () use ($id) {
            $transferOrder = TransferOrder::findOrFail($id);

            $transferOrder->items()->delete();

            $transferOrder->delete();
        });

        return redirect()->route('transfer-orders.index');
    }

    public function status(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::調撥管理);

        $attributes = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(TransferOrderStatusEnum::cases(), 'value')),
        ]);

        DB::transaction(function () use ($id, $attributes) {
            $transferOrder = TransferOrder::with('items.variant.product')->findOrFail($id);

            if (!$transferOrder->status->canFlowTo($attributes['status'])) {
                throw new \Exception("非法狀態轉換：{$transferOrder->status->value} → {$attributes['status']}");
            }

            switch (TransferOrderStatusEnum::from($attributes['status'])) {
                case TransferOrderStatusEnum::調撥中:
                    $transferOrder->updateQuietly([
                        'arrival_status' => TransferOrderArrivalStatusEnum::未到貨,
                        'status' => TransferOrderStatusEnum::調撥中,
                    ]);
                    break;
                case TransferOrderStatusEnum::已入庫:
                    $transferOrder->updateQuietly([
                        'arrival_status' => TransferOrderArrivalStatusEnum::已到貨,
                        'status' => TransferOrderStatusEnum::已入庫,
                    ]);
                    break;
                default:
                    throw new \Exception("無法轉換狀態: {$transferOrder->status->value} → {$attributes['status']}");
            }

            $transferOrder->refresh();

            foreach ($transferOrder->items as $orderItem) {
                event(new InventoryChanged(
                    type: match ($transferOrder->status) {
                        TransferOrderStatusEnum::調撥中 => InventoryLogTypeEnum::調撥單移出,
                        TransferOrderStatusEnum::已入庫 => InventoryLogTypeEnum::調撥單入庫,
                    },
                    variantId: $orderItem->variant_id,
                    locationId: match ($transferOrder->status) {
                        TransferOrderStatusEnum::調撥中 => $transferOrder->from_location_id,
                        TransferOrderStatusEnum::已入庫 => $transferOrder->to_location_id,
                    },
                    quantity: $orderItem->quantity,
                    causer: auth()->user(),
                    reference: $orderItem,
                    properties: $orderItem->load('transferOrder:id,order_number')->toArray(),
                ));
            }
        });

        return redirect()->route('transfer-orders.index');
    }
}
