<?php

namespace App\Http\Controllers;

use App\Enums\InventoryLog\InventoryLogTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Variant;
use App\Repositories\OrderItemRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;
use App\Enums\PurchaseOrder\PurchaseOrderStatusEnum;
use App\Enums\PurchaseOrder\PurchaseOrderArrivalStatusEnum;
use App\Enums\PurchaseOrder\PurchaseOrderTypeEnum;
use App\Events\InventoryChanged;
use App\Models\OrderItem;
use App\Models\PurchaseOrderItem;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\VariantRepository;
use App\Exports\PurchaseOrderExport;
use Maatwebsite\Excel\Facades\Excel;

class AdminPurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderRepository $purchaseOrderRepository, protected OrderItemRepository $orderItemRepository, protected VariantRepository $variantRepository, protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                ValidatorInterface::RULE_CREATE => [
                    'issuer_user_id'                        => 'required|integer|exists:users,id',
                    'executor_user_id'                      => 'nullable|integer|exists:users,id',
                    'location_id'                           => 'required|integer|exists:locations,id',
                    'type'                                  => 'required|string|in:' . implode(',', array_column(PurchaseOrderTypeEnum::cases(), 'value')),
                    'custom_number'                         => 'nullable|string',
                    'other_fee'                             => 'required|numeric|min:0',
                    'total_amount'                          => 'required|numeric|min:0',
                    'remark'                                => 'nullable|string',
                    'scheduled_time'                        => 'nullable|date',
                    // items 項目
                    'items'                                 => 'required|array',
                    'items.*'                               => [function ($attribute, $value, $fail) {
                        $attributeIndex = explode('.', $attribute)[1];

                        $seen = [];

                        foreach (request('items') as $index => $item) {
                            $variantId = $item['variant_id'] ?? null;

                            $orderItemId = $item['order_item_id'] ?? null;

                            $key = $variantId . '::' . $orderItemId;

                            if (in_array($key, $seen, true) && $attributeIndex == $index) return $fail("第 " . ($index + 1) . " 筆資料中，商品規格與訂單項目重複。");

                            $seen[] = $key;
                        }
                    }],
                    'items.*.order_item_id'                 => 'nullable|integer|exists:order_items,id',
                    'items.*.variant_id'                    => 'nullable|integer|exists:variants,id',
                    'items.*.product_name'                  => 'nullable|string',
                    'items.*.variant_name'                  => 'nullable|string',
                    'items.*.quantity'                      => [
                        'required',
                        'integer',
                        'min:1',
                        function ($attribute, $value, $fail) {
                            $index = explode('.', $attribute)[1];

                            if (!$orderItemId = request('items.' . $index . '.order_item_id')) return;

                            if (!($orderItem = OrderItem::withSum('purchaseOrderItems', 'quantity')->find($orderItemId))) return;

                            $remainingQuantity = $orderItem->quantity - ($orderItem->purchase_order_items_sum_quantity ?? 0);

                            if ($value > $remainingQuantity) $fail("進貨數量不能超過訂單剩餘可進貨數量 ({$remainingQuantity})");
                        }
                    ],
                    'items.*.cost_price'                    => 'required|numeric|min:0',
                ],
                ValidatorInterface::RULE_UPDATE => [
                    'issuer_user_id'                        => 'required|integer|exists:users,id',
                    'executor_user_id'                      => 'nullable|integer|exists:users,id',
                    'location_id'                           => 'required|integer|exists:locations,id',
                    'custom_number'                         => 'nullable|string',
                    'other_fee'                             => 'required|numeric|min:0',
                    'total_amount'                          => 'required|numeric|min:0',
                    'remark'                                => 'nullable|string',
                    'scheduled_time'                        => 'nullable|date',
                    // items 項目
                    'items'                                 => 'required|array',
                    'items.*'                               => [function ($attribute, $value, $fail) {
                        $attributeIndex = explode('.', $attribute)[1];

                        $seen = [];

                        foreach (request('items') as $index => $item) {
                            $variantId = $item['variant_id'] ?? null;

                            if (!$variantId) continue;

                            $orderItemId = $item['order_item_id'] ?? null;

                            $key = $variantId . '::' . $orderItemId;

                            if (in_array($key, $seen, true) && $attributeIndex == $index) return $fail("第 " . ($index + 1) . " 筆資料中，商品規格與訂單項目重複。");

                            $seen[] = $key;
                        }
                    }],
                    'items.*.id'                            => 'nullable|integer|exists:purchase_order_items,id',
                    'items.*.order_item_id'                 => 'nullable|integer|exists:order_items,id',
                    'items.*.variant_id'                    => 'nullable|integer|exists:variants,id',
                    'items.*.product_name'                  => 'nullable|string',
                    'items.*.variant_name'                  => 'nullable|string',
                    'items.*.quantity'                      => [
                        'required',
                        'integer',
                        'min:1',
                        function ($attribute, $value, $fail) {
                            $index = explode('.', $attribute)[1];

                            if (!$orderItemId = request('items.' . $index . '.order_item_id')) return;

                            if (!($orderItem = OrderItem::withSum(['purchaseOrderItems' => fn ($q) => $q->whereRelation('purchaseOrder', fn ($q) => $q->where('status', '!=', PurchaseOrderStatusEnum::已取消))], 'quantity')->find($orderItemId))) return;

                            $purchaseOrderItem = PurchaseOrderItem::find(request('items.' . $index . '.id'));

                            $remainingQuantity = $orderItem->quantity - ($orderItem->purchase_order_items_sum_quantity ?? 0) + ($purchaseOrderItem?->quantity ?? 0);

                            if ($value > $remainingQuantity) $fail("進貨數量不能超過訂單剩餘可進貨數量 ({$remainingQuantity})");
                        }
                    ],
                    'items.*.cost_price'                    => 'required|numeric|min:0',
                ],
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
        Gate::authorize(PermissionNameEnum::進貨管理);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('PurchaseOrder/Index', [
            'purchaseOrders' => $this->purchaseOrderRepository->getOrders($attributes),
            'locations' => Location::query()->pluck('name', 'id'),
            'status' => array_column(PurchaseOrderStatusEnum::cases(), 'name', 'value'),
            'arrivalStatus' => array_column(PurchaseOrderArrivalStatusEnum::cases(), 'name', 'value'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        return Inertia::render('PurchaseOrder/CreateOrEdit', [
            'variants' => Inertia::lazy(fn () => $this->variantRepository->getVariantsWithRelations($request->all())),
            'users' => User::all(),
            'locations' => Location::all(),
            'ordersItems' => $this->orderItemRepository->getOrderItemsForPurchasing($request->all()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        $record = DB::transaction(function () use ($attributes) {
            $order = PurchaseOrder::query()->create([
                'issuer_user_id'        => $attributes['issuer_user_id'],
                'executor_user_id'      => $attributes['executor_user_id'] ?? null,
                'location_id'           => $attributes['location_id'] ?? null,
                'order_number'          => 'PO' . date('YmdHis'),
                'type'                  => $attributes['type'],
                'custom_number'         => $attributes['custom_number'] ?? null,
                'other_fee'             => $fee = ($attributes['other_fee'] ?? 0),
                'total_amount'          => collect($attributes['items'])->sum(fn ($item) => $item['cost_price'] * $item['quantity']) + $fee,
                'remark'                => $attributes['remark'] ?? null,
                'scheduled_time'        => $attributes['scheduled_time'] ?? null,
            ]);

            foreach ($attributes['items'] as $item) {
                $variant = Variant::find($item['variant_id']);

                $order->items()->create([
                    'order_item_id' => $item['order_item_id'] ?? null,
                    'product_id' => $variant?->product_id ?? null,
                    'variant_id' => $variant?->getKey() ?? null,
                    'product_name' => $item['product_name'] ?? $variant?->product?->name,
                    'variant_name' => $item['variant_name'] ?? $variant?->name,
                    'quantity' => $item['quantity'],
                    'price' => $variant?->price ?? 0,
                    'cost_price' => $item['cost_price'],
                    'total_cost' => $item['cost_price'] * $item['quantity'],
                ]);
            }

            return $order;
        });


        return redirect()->route('purchase-orders.edit', $record->getKey())->with('id', $record->getKey());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        return Inertia::render('PurchaseOrder/CreateOrEdit', [
            'purchaseOrder' => PurchaseOrder::query()->with('items.variant.inventories', 'items.order', 'user', 'location')->findOrFail($id),
            'variants' => Inertia::lazy(fn () => $this->variantRepository->getVariantsWithRelations($request->all())),
            'users' => User::all(),
            'locations' => Location::all(),
            'ordersItems' => $this->orderItemRepository->getOrderItemsForPurchasing($request->all()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes, $id) {
            $order = PurchaseOrder::query()->findOrFail($id);

            // 檢查訂單狀態是否允許完整編輯
            if (!$order->status->canEdit()) {
                foreach ($attributes['items'] as $item) {
                    $order->items()->updateOrCreate(['id' => $item['id']], [
                        'cost_price' => $item['cost_price'],
                        'total_cost' => $item['cost_price'] * $item['quantity'],
                    ]);
                }

                // 狀態不允許編輯時，只更新允許的欄位
                $order->updateQuietly([
                    'executor_user_id'      => $attributes['executor_user_id'] ?? null,
                    'custom_number'         => $attributes['custom_number'] ?? null,
                    'other_fee'             => $attributes['other_fee'],
                    'total_amount'          => $order->items()->sum('total_cost') + $attributes['other_fee'],
                    'remark'                => $attributes['remark'] ?? null,
                    'scheduled_time'        => $attributes['scheduled_time'] ?? null,
                    'updated_at'            => now(),
                ]);

                return $order;
            }

            // 狀態允許編輯時，更新項目和所有欄位
            $order->items()->whereNotIn('id', array_filter(array_column($attributes['items'], 'id')))->delete();

            foreach ($attributes['items'] as $item) {
                $variant = Variant::find($item['variant_id'] ?? null);

                $order->items()->updateOrCreate(['id' => $item['id'] ?? null], [
                    'order_item_id' => $item['order_item_id'] ?? null,
                    'product_id' => $variant?->product_id ?? null,
                    'variant_id' => $variant?->getKey() ?? null,
                    'product_name' => $item['product_name'] ?? $variant?->product?->name,
                    'variant_name' => $item['variant_name'] ?? $variant?->name,
                    'quantity' => $item['quantity'],
                    'price' => $variant?->price ?? 0,
                    'cost_price' => $item['cost_price'],
                    'total_cost' => $item['cost_price'] * $item['quantity'],
                ]);
            }

            $order->update([
                'issuer_user_id'        => $attributes['issuer_user_id'],
                'executor_user_id'      => $attributes['executor_user_id'] ?? null,
                'location_id'           => $attributes['location_id'] ?? null,
                'custom_number'         => $attributes['custom_number'] ?? null,
                'other_fee'             => $attributes['other_fee'],
                'total_amount'          => $order->items()->sum('total_cost') + $attributes['other_fee'],
                'remark'                => $attributes['remark'] ?? null,
                'scheduled_time'        => $attributes['scheduled_time'] ?? null,
                'updated_at'            => now(),
            ]);
        });

        return redirect()->route('purchase-orders.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        DB::transaction(function () use ($id) {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            $purchaseOrder->items()->delete();

            $purchaseOrder->delete();
        });

        return redirect()->route('purchase-orders.index');
    }

    public function status(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        $attributes = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(PurchaseOrderStatusEnum::cases(), 'value')),
        ]);

        DB::transaction(function () use ($id, $attributes) {
            $purchaseOrder = PurchaseOrder::with('items.variant.product')->findOrFail($id);

            if (!$purchaseOrder->status->canFlowTo($attributes['status'])) {
                throw new \Exception("非法狀態轉換：{$purchaseOrder->status->value} → {$attributes['status']}");
            }

            switch (PurchaseOrderStatusEnum::from($attributes['status'])) {
                case PurchaseOrderStatusEnum::已完成:
                    $purchaseOrder->updateQuietly([
                        'arrival_status' => match ($purchaseOrder->type) {
                            PurchaseOrderTypeEnum::進貨 => PurchaseOrderArrivalStatusEnum::已到貨,
                            PurchaseOrderTypeEnum::退貨 => PurchaseOrderArrivalStatusEnum::已退貨,
                        },
                        'status' => PurchaseOrderStatusEnum::已完成,
                    ]);
                    break;
                case PurchaseOrderStatusEnum::已取消:
                    $purchaseOrder->updateQuietly([
                        'arrival_status' => match ($purchaseOrder->type) {
                            PurchaseOrderTypeEnum::進貨 => PurchaseOrderArrivalStatusEnum::已退貨,
                        },
                        'status' => PurchaseOrderStatusEnum::已取消,
                    ]);
                    break;
                default:
                    throw new \Exception("無法轉換狀態: {$purchaseOrder->status->value} → {$attributes['status']}");
            }

            $purchaseOrder->refresh();

            foreach ($purchaseOrder->items as $orderItem) {
                event(new InventoryChanged(
                    type: match ($purchaseOrder->type) {
                        PurchaseOrderTypeEnum::進貨 => match ($purchaseOrder->status) {
                            PurchaseOrderStatusEnum::已完成 => InventoryLogTypeEnum::進貨單入庫,
                            PurchaseOrderStatusEnum::已取消 => InventoryLogTypeEnum::進貨單取消,
                        },
                        PurchaseOrderTypeEnum::退貨 => match ($purchaseOrder->status) {
                            PurchaseOrderStatusEnum::已完成 => InventoryLogTypeEnum::退貨單出庫,
                        },
                    },
                    variantId: $orderItem->variant_id,
                    locationId: $purchaseOrder->location_id,
                    quantity: $orderItem->quantity,
                    causer: auth()->user(),
                    reference: $orderItem,
                    properties: $orderItem->load('purchaseOrder:id,order_number')->toArray(),
                ));
            }
        });

        return redirect()->back();
    }

    public function print(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        $data = PurchaseOrder::query()
            ->with(['items.variant.product', 'items.order.customer', 'user', 'executorUser', 'location'])
            ->findOrFail($id);

        $groupSameItems = $request->boolean('group_same_items', false);

        if ($groupSameItems) {
            $data->items = $this->groupSameItemsForPrint($data->items);
        }

        $filename = ($data->type == 'purchase' ? '進貨單' : '退貨單') . '_' . $data->order_number . '.pdf';

        return \App\Helpers\DomPDFHelper::render('pdf.purchase-order', [
            'purchaseOrder' => $data,
            'groupSameItems' => $groupSameItems
        ])->stream($filename);
    }

    public function printWithoutAmounts(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        $data = PurchaseOrder::query()
            ->with(['items.variant.product', 'items.order.customer', 'user', 'executorUser', 'location'])
            ->findOrFail($id);

        $groupSameItems = $request->boolean('group_same_items', false);

        if ($groupSameItems) {
            $data->items = $this->groupSameItemsForPrint($data->items);
        }

        $filename = ($data->type == 'purchase' ? '進貨單' : '退貨單') . '_' . $data->order_number . '_無金額.pdf';

        return \App\Helpers\DomPDFHelper::render('pdf.purchase-order', [
            'purchaseOrder' => $data,
            'hideAmounts' => true,
            'groupSameItems' => $groupSameItems
        ])->stream($filename);
    }

    public function export(Request $request)
    {
        Gate::authorize(PermissionNameEnum::進貨管理);

        $filename = '進貨單明細_' . now('Asia/Taipei')->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new PurchaseOrderExport(), $filename);
    }

    private function groupSameItemsForPrint($items)
    {
        $groupedItems = collect();
        $grouped = [];

        foreach ($items as $item) {
            $isFromOrder = !empty($item->order_item_id);
            $key = $item->variant_id . '-' . $item->product_id;

            if (isset($grouped[$key])) {
                $existing = $grouped[$key];
                $totalQuantity = $existing['quantity'] + $item->quantity;
                $totalCost = ($existing['quantity'] * $existing['cost_price']) + ($item->quantity * $item->cost_price);
                $avgCostPrice = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;

                $grouped[$key] = [
                    'product_name' => $existing['product_name'],
                    'variant_name' => $existing['variant_name'],
                    'quantity' => $totalQuantity,
                    'price' => $existing['price'], // 保持第一個的價格
                    'cost_price' => $avgCostPrice,
                    'total_cost' => $totalQuantity * $avgCostPrice,
                    'order_items_count' => $existing['order_items_count'] + ($isFromOrder ? 1 : 0),
                    'grouped_count' => $existing['grouped_count'] + 1,
                ];
            } else {
                $grouped[$key] = [
                    'product_name' => $item->variant->product->name ?? $item->product_name,
                    'variant_name' => $item->variant->name ?? $item->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'cost_price' => $item->cost_price,
                    'total_cost' => $item->total_cost,
                    'order_items_count' => $isFromOrder ? 1 : 0,
                    'grouped_count' => 1,
                ];
            }
        }

        // 轉換為集合並排序
        foreach ($grouped as $item) {
            $groupedItems->push($item);
        }

        return $groupedItems->sortBy('product_name')->sortBy('variant_name')->values();
    }
}
