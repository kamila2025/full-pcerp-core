<?php

namespace App\Http\Controllers;

use App\Enums\Address\AddressTypeEnum;
use App\Enums\Fulfillment\FulfillmentCompanyEnum;
use App\Enums\Fulfillment\FulfillmentServiceEnum;
use App\Enums\Gateway\GatewayTypeEnum;
use App\Enums\Order\OrderDeliveryTypeEnum;
use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Order\OrderFulfillmentStatusEnum;
use App\Enums\Order\OrderSourceTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\PurchaseOrder\PurchaseOrderStatusEnum;
use App\Enums\PurchaseOrder\PurchaseOrderTypeEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Events\OrderSavedFinancialStatus;
use App\Events\OrderSavedFulfillmentStatus;
use App\Exports\OrderExport;
use App\Helpers\DomPDFHelper;
use App\Models\Customer;
use App\Models\FormTemplate;
use App\Models\Fulfillment;
use App\Models\Gateway;
use App\Models\Location;
use App\Models\Logistics;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Repositories\OrderRepository;
use App\Repositories\VariantRepository;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\LaravelValidator;

class AdminOrderController extends Controller
{
    public function __construct(protected OrderService $orderService, protected OrderRepository $orderRepository, protected VariantRepository $variantRepository, protected LaravelValidator $validator)
    {
        $this->validator
            ->setRules([
                ValidatorInterface::RULE_CREATE => [
                    'attribution_user.id'                   => 'nullable|integer|exists:users,id',
                    'location.id'                           => 'nullable|integer|exists:locations,id',
                    'shipping_location.id'                  => 'required|integer|exists:locations,id',
                    'template_id'                           => 'nullable|integer|exists:form_templates,id',
                    'delivery_type'                         => 'nullable|string|in:shipping,pickup',
                    'total_discount'                        => 'nullable|numeric',
                    'total_shipping'                        => 'nullable|numeric',
                    'total_tax'                             => 'nullable|numeric',
                    'custom_amount'                         => 'nullable|numeric',
                    'note'                                  => 'nullable|string',
                    'remark'                                => 'nullable|string',
                    'status'                                => 'nullable|string|in:draft,open', // 只允許草稿或開啟
                    // 客户
                    'customer.id'                           => 'nullable|exists:customers,id',
                    'customer.name'                         => 'nullable|string',
                    'customer.email'                        => 'nullable|string',
                    'customer.phone'                        => 'nullable|string',
                    'customer.additional.customer_source'   => 'nullable|string', // 客戶來源
                    'customer.additional.id_number'         => 'nullable|string', // 身分證
                    'customer.additional.carrier'           => 'nullable|string', // 電信
                    // 配送地址
                    'shipping_address.full_name'            => 'nullable',
                    'shipping_address.company'              => 'nullable',
                    'shipping_address.address1'             => 'nullable',
                    'shipping_address.email'                => 'nullable',
                    'shipping_address.phone'                => 'nullable',
                    // 自取地址
                    'pickup_address.location.id'            => 'nullable|exists:locations,id',
                    'pickup_address.full_name'              => 'nullable',
                    'pickup_address.email'                  => 'nullable',
                    'pickup_address.phone'                  => 'nullable',
                    //
                    'items'                                 => 'required|array',
                    'items.*.template_item_id'              => 'nullable|integer|exists:form_template_items,id',
                    'items.*.variant_id'                    => 'nullable|integer|distinct|exists:variants,id',
                    'items.*.product_name'                  => 'nullable|string',
                    'items.*.variant_name'                  => 'nullable|string',
                    'items.*.price'                         => 'nullable|numeric',
                    'items.*.cost_price'                    => 'nullable|numeric',
                    'items.*.quantity'                      => 'required|integer',
                    // 運費
                    'shipping_fees.*.logistic_id'           => 'nullable',
                    'shipping_fees.*.name'                  => 'nullable|string',
                    'shipping_fees.*.price'                 => 'nullable|numeric',
                    'shipping_fees.*.total_discount'        => 'nullable|numeric',
                    // 稅金
                    'tax_included'                          => 'nullable|boolean',
                    'tax_rate'                              => 'nullable|numeric',
                ],
                ValidatorInterface::RULE_UPDATE => [
                    'attribution_user.id'                   => 'nullable|integer|exists:users,id',
                    'location.id'                           => 'nullable|integer|exists:locations,id',
                    'template_id'                           => 'nullable|integer|exists:form_templates,id',
                    'delivery_type'                         => 'required|string|in:shipping,pickup,others',
                    'total_discount'                        => 'nullable|numeric',
                    'total_shipping'                        => 'nullable|numeric',
                    'total_tax'                             => 'nullable|numeric',
                    'custom_amount'                         => 'nullable|numeric',
                    'note'                                  => 'nullable|string',
                    'remark'                                => 'nullable|string',
                    'status'                                => 'nullable|string|in:open', // 只允許開啟
                    // 客户
                    'customer.id'                           => 'nullable|exists:customers,id',
                    'customer.name'                         => 'nullable|string',
                    'customer.email'                        => 'nullable|string',
                    'customer.phone'                        => 'nullable|string',
                    'customer.additional.customer_source'   => 'nullable|string', // 客戶來源
                    'customer.additional.id_number'         => 'nullable|string', // 身分證
                    'customer.additional.carrier'           => 'nullable|string', // 電信
                    // 配送地址
                    'shipping_address.id'                   => 'nullable|exists:addresses,id',
                    'shipping_address.full_name'            => 'nullable',
                    'shipping_address.company'              => 'nullable',
                    'shipping_address.address1'             => 'nullable',
                    'shipping_address.email'                => 'nullable',
                    'shipping_address.phone'                => 'nullable',
                    // 自取地址
                    'pickup_address.id'                     => 'nullable|exists:addresses,id',
                    'pickup_address.location.id'            => 'nullable|exists:locations,id',
                    'pickup_address.full_name'              => 'nullable',
                    'pickup_address.email'                  => 'nullable',
                    'pickup_address.phone'                  => 'nullable',
                    //
                    'items'                                 => 'required|array',
                    'items.*.template_item_id'              => 'nullable|integer',
                    'items.*.id'                            => 'nullable|integer|exists:order_items,id',
                    'items.*.variant_id'                    => 'nullable|integer|distinct|exists:variants,id',
                    'items.*.product_name'                  => 'nullable|string',
                    'items.*.variant_name'                  => 'nullable|string',
                    'items.*.price'                         => 'required|numeric',
                    'items.*.cost_price'                    => 'required|numeric',
                    'items.*.quantity'                      => 'required|integer',
                    // 運費
                    'shipping_fees.*.id'                    => 'nullable|integer',
                    'shipping_fees.*.logistic_id'           => 'nullable',
                    'shipping_fees.*.name'                  => 'nullable|string',
                    'shipping_fees.*.price'                 => 'nullable|numeric',
                    'shipping_fees.*.total_discount'        => 'nullable|numeric',
                    // 稅金
                    'tax_included'                          => 'nullable|boolean',
                    'tax_rate'                              => 'nullable|numeric',
                ],
            ])
            ->setMessages([
                //
            ])
            ->setAttributes([
                'attribution_user.id'                   => '歸屬業務',
                'location.id'                           => '地點',
                'template_id'                           => '模板',
                'delivery_type'                         => '配送方式',
                'total_discount'                        => '折扣',
                'total_shipping'                        => '運費',
                'total_tax'                             => '稅金',
                'amount'                                => '金額',
                // 客户
                'customer.id'                           => '客戶 ID',
                'customer.name'                         => '客戶名稱',
                // 配送地址
                'shipping_address.full_name'            => '收件人姓名',
                // 自取地址
                'pickup_address.location_id'            => '自取地點 ID',
                'pickup_address.full_name'              => '自取人姓名',
                'items'                                 => '訂單項目',
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate([
            'page'      => 'nullable|integer',
            'per_page'  => 'nullable|max:100|integer|multiple_of:5',
        ]);

        return Inertia::render('Order/Index', [
            'orders' => $this->orderRepository->getOrders($attributes),
            'templates' => FormTemplate::query()->get(),
            'users' => User::query()->pluck('name', 'id'),
            'locations' => Location::query()->pluck('name', 'id'),
            'financialStatus' => array_column(OrderFinancialStatusEnum::cases(), 'name', 'value'),
            'fulfillmentStatus' => array_column(OrderFulfillmentStatusEnum::cases(), 'name', 'value'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate([
            'template_id'   => 'nullable|integer|exists:form_templates,id',
        ]);

        return Inertia::render('Order/CreateOrEdit', [
            'variants' => Inertia::lazy(fn () => $this->variantRepository->getVariantsWithRelations($request->all())),
            'template' => FormTemplate::query()->with('items')->find($attributes['template_id'] ?? null),
            'customers' => Inertia::lazy(fn () => Customer::query()->get()),
            'locations' => Inertia::lazy(fn () => Location::query()->get()),
            'logistics' => Logistics::query()->where('is_enabled', true)->get(),
            'users' => Inertia::lazy(fn () => User::query()->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate($this->validator->getRules(ValidatorInterface::RULE_CREATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes) {
            if (isset($attributes['customer']['id']) || isset($attributes['customer']['name'])) {
                $customer = Customer::query()->findOr(($attributes['customer']['id'] ?? null), '*', fn () => Customer::create([
                    'attribution_user_id'   => auth()->id(),
                    'name'                  => $attributes['customer']['name'],
                    'email'                 => $attributes['customer']['email'] ?? null,
                    'phone'                 => $attributes['customer']['phone'] ?? null,
                    'additional'            => $attributes['customer']['additional'] ?? null,
                ]));
            }

            $order = Order::query()->create([
                'source_type'           => OrderSourceTypeEnum::ERP,
                'delivery_type'         => $attributes['delivery_type'] ?? null,
                'customer_id'           => $customer?->getKey() ?? null,
                'attribution_user_id'   => $attributes['attribution_user']['id'] ?? null,
                'location_id'           => $attributes['location']['id'] ?? null,
                'shipping_location_id'  => $attributes['shipping_location']['id'],
                'template_id'           => $attributes['template_id'],
                'order_number'          => 'ORD' . date('YmdHis'),
                'status'                => $attributes['status'] ?? OrderStatusEnum::開啟,
            ]);

            if ($order->delivery_type === OrderDeliveryTypeEnum::運送) {
                $order->addresses()->create([
                    'address_type' => AddressTypeEnum::訂單配送,
                    'full_name' => $attributes['shipping_address']['full_name'] ?? null,
                    'company' => $attributes['shipping_address']['company'] ?? null,
                    'address1' => $attributes['shipping_address']['address1'] ?? null,
                    'email' => $attributes['shipping_address']['email'] ?? null,
                    'phone' => $attributes['shipping_address']['phone'] ?? null,
                ]);
            }

            if ($order->delivery_type === OrderDeliveryTypeEnum::自取) {
                $location = Location::query()->find($attributes['pickup_address']['location']['id'] ?? null);

                $order->addresses()->create([
                    'address_type' => AddressTypeEnum::訂單自取,
                    'location_id' => $location?->getKey() ?? null,
                ]);
            }

            // 更新訂單項目
            $this->orderService->updateOrderItems($attributes['items'], $order->id);

            // 更新運費
            $this->orderService->updateOrderFees($attributes['shipping_fees'] ?? [], $order->id);

            /**
             * 更新訂單計算
             */
            $order->subtotal_price = $order->items()->sum('total_amount');

            $order->total_discount = $attributes['total_discount'] ?? 0;

            $order->total_shipping = $order->shippingFees()->sum('price') - $order->shippingFees()->sum('total_discount');

            $order->tax_included = $attributes['tax_included'] ?? false;

            $order->tax_rate = $attributes['tax_rate'] ?? 0;

            $order->total_price = $order->items()->sum('total_amount') - $order->total_discount + $order->total_shipping;

            $order->custom_amount = $attributes['custom_amount'] ?? 0;

            $order->amount = $order->custom_amount ? $order->custom_amount : $order->total_price;

            $order->total_tax = $order->tax_included ? round($order->amount - ($order->amount / (1 + $order->tax_rate / 100))) : round($order->amount * ($order->tax_rate / 100));

            $order->amount += $order->tax_included ? 0 : $order->total_tax;

            $order->saveQuietly();
        });

        return redirect()->route('orders.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $order = $this->orderRepository->getOrderWithRelations($id);

        // dd($order->toArray());

        return Inertia::render('Order/CreateOrEdit', [
            'order' => $order,
            'variants' => Inertia::lazy(fn () => $this->variantRepository->getVariantsWithRelations($request->all())),
            'template' => FormTemplate::query()->with('items')->find($order->template_id),
            'customers' => Inertia::lazy(fn () => Customer::query()->get()),
            'locations' => Inertia::lazy(fn () => Location::query()->get()),
            'users' => Inertia::lazy(fn () => User::query()->get()),
            'gatewaies' => Gateway::query()->where('is_enabled', true)->orderBy('position')->get(),
            'services' => array_column(FulfillmentServiceEnum::cases(), 'name', 'value'),
            'logistics' => Logistics::query()->where('is_enabled', true)->get(),
            'companies' => array_column(FulfillmentCompanyEnum::cases(), 'name', 'value'),
            'purchaseOrders' => Inertia::lazy(fn () => PurchaseOrder::whereIn('status', [PurchaseOrderStatusEnum::新開單, PurchaseOrderStatusEnum::進貨中])->get()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate($this->validator->setId($id)->getRules(ValidatorInterface::RULE_UPDATE), $this->validator->getMessages(), $this->validator->getAttributes());

        DB::transaction(function () use ($attributes, $id) {
            if (isset($attributes['customer']['id']) || isset($attributes['customer']['name'])) {
                $customer = Customer::query()->findOr(($attributes['customer']['id'] ?? null), '*', fn () => Customer::create([
                    'attribution_user_id'   => auth()->id(),
                    'name'                  => $attributes['customer']['name'],
                    'email'                 => $attributes['customer']['email'] ?? null,
                    'phone'                 => $attributes['customer']['phone'] ?? null,
                    'additional'            => $attributes['customer']['additional'] ?? null,
                ]));
            }

            $order = Order::query()->findOrFail($id);

            $order->fill([
                'customer_id'           => $customer?->getKey() ?? null,
                'attribution_user_id'   => $attributes['attribution_user']['id'] ?? null,
                'location_id'           => $attributes['location']['id'] ?? null,
                'delivery_type'         => $attributes['delivery_type'] ?? null,
                'note'                  => $attributes['note'] ?? null,
                'remark'                => $attributes['remark'] ?? null,
                'status'                => $attributes['status'] ?? $order->status,
            ]);

            if ($order->delivery_type === OrderDeliveryTypeEnum::運送) {
                $order->addresses()->where('id', '!=', $attributes['shipping_address']['id'] ?? null)->delete();

                $order->shippingAddress()->updateOrCreate(['id' => $attributes['shipping_address']['id'] ?? null], [
                    'address_type' => AddressTypeEnum::訂單配送,
                    'full_name' => $attributes['shipping_address']['full_name'] ?? null,
                    'company' => $attributes['shipping_address']['company'] ?? null,
                    'address1' => $attributes['shipping_address']['address1'] ?? null,
                    'email' => $attributes['shipping_address']['email'] ?? null,
                    'phone' => $attributes['shipping_address']['phone'] ?? null,
                ]);
            }

            if ($order->delivery_type === OrderDeliveryTypeEnum::自取) {
                $order->addresses()->where('id', '!=', $attributes['pickup_address']['id'] ?? null)->delete();

                $order->pickupAddress()->updateOrCreate(['id' => $attributes['pickup_address']['id'] ?? null], [
                    'address_type' => AddressTypeEnum::訂單自取,
                    'location_id' => $attributes['pickup_address']['location']['id'] ?? null,
                ]);
            }

            // 更新訂單項目
            $this->orderService->updateOrderItems($attributes['items'], $order);

            // 更新運費
            $this->orderService->updateOrderFees($attributes['shipping_fees'] ?? [], $order->id);

            /**
             * 更新訂單計算
             */
            $order->subtotal_price = $order->items()->sum('total_amount');

            $order->total_discount = $attributes['total_discount'] ?? 0;

            $order->total_shipping = $order->shippingFees()->sum('price') - $order->shippingFees()->sum('total_discount');

            $order->tax_included = $attributes['tax_included'] ?? false;

            $order->tax_rate = $attributes['tax_rate'] ?? 0;

            $order->total_price = $order->items()->sum('total_amount') - $order->total_discount + $order->total_shipping;

            $order->custom_amount = $attributes['custom_amount'] ?? 0;

            $order->amount = $order->custom_amount ? $order->custom_amount : $order->total_price;

            $order->total_tax = $order->tax_included ? round($order->amount - ($order->amount / (1 + $order->tax_rate / 100))) : round($order->amount * ($order->tax_rate / 100));

            $order->amount += $order->tax_included ? 0 : $order->total_tax;

            $order->save();

            event(new OrderSavedFinancialStatus($order));

            event(new OrderSavedFulfillmentStatus($order));
        });

        return redirect()->route('orders.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        DB::transaction(function () use ($id) {
            $order = Order::findOrFail($id);

            $this->orderService->deleteOrderItems($order->getKey());

            $order->delete();
        });

        return redirect()->back();
    }

    public function status(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(OrderStatusEnum::cases(), 'value')),
        ]);

        DB::transaction(function () use ($id, $attributes) {
            $order = Order::findOrFail($id);

            // Check if the status transition is allowed
            $allowedTransitions = [
                'draft' => ['open'],
                'open' => ['cancelled', 'archived'],
                'cancelled' => ['open'],
                'archived' => ['open']
            ];

            if (!in_array($attributes['status'], $allowedTransitions[$order->status->value] ?? [])) {
                throw new \Exception("非法狀態轉換：{$order->status->value} → {$attributes['status']}");
            }

            if ($attributes['status'] === 'archived' && $order->fulfillment_status !== OrderFulfillmentStatusEnum::已出貨) {
                throw new \Exception('訂單未出貨，無法封存');
            }

            if ($attributes['status'] === 'archived' && $order->financial_status !== OrderFinancialStatusEnum::已付款) {
                throw new \Exception('訂單未付款，無法封存');
            }

            $order->update([
                'status' => OrderStatusEnum::from($attributes['status'])
            ]);
        });

        return redirect()->back();
    }

    public function transactions(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate([
            'gateway_id'        => 'required|integer|exists:gateways,id',
            'type'              => ['required', \Illuminate\Validation\Rule::enum(GatewayTypeEnum::class)],
            'method'            => 'nullable',
            'amount'            => 'required|numeric|min:1',
            'note'              => 'nullable|string',
            'reference'         => 'nullable|required_if:type,bank-transfer|numeric|min:0',
            'additional.period' => 'nullable|required_if:type,installment|integer',
            'additional.amount' => 'nullable|required_if:type,installment|integer',
        ]);

        try {
            DB::transaction(function () use ($attributes, $id) {
                $gateway = Gateway::find($attributes['gateway_id']);

                $subGateway = current(array_filter($gateway['sub_gateways'], fn ($item) => $item['gateway_method'] === $attributes['method']));

                Transaction::create([
                    'order_id'          => $id,
                    'gateway_type'      => $gateway->type,
                    'gateway_title'     => $gateway->title,
                    'gateway_method'    => $attributes['method'] ?? null,
                    'amount'            => $attributes['amount'],
                    'fee'               => $attributes['amount'] * ($subGateway['fee_rate'] / 100),
                    'note'              => $attributes['note'] ?? null,
                    'reference'         => $attributes['reference'] ?? null,
                    'additional'        => $attributes['additional'] ?? null,
                    'status'            => match (GatewayTypeEnum::tryFrom($attributes['type'])) {
                        GatewayTypeEnum::萬事達 => TransactionStatusEnum::建立中,
                        GatewayTypeEnum::黑貓Pay => TransactionStatusEnum::建立中,
                        default => TransactionStatusEnum::處理中,
                    },
                    'short_code'        => match (GatewayTypeEnum::tryFrom($attributes['type'])) {
                        GatewayTypeEnum::萬事達 => strtolower(\Illuminate\Support\Str::random(4)),
                        GatewayTypeEnum::黑貓Pay => strtolower(\Illuminate\Support\Str::random(4)),
                        default => null,
                    },
                ]);
            });

            return redirect()->route('orders.edit', ['order' => $id]);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function fulfillments(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate([
            'service'           => ['nullable', \Illuminate\Validation\Rule::enum(FulfillmentServiceEnum::class)],
            'tracking_company'  => ['nullable', 'required_if:service,manual', \Illuminate\Validation\Rule::enum(FulfillmentCompanyEnum::class)],
            'tracking_number'   => 'nullable|string',
            'items'             => 'required|array',
            'items.*.order_id'  => 'required|integer|exists:order_items,id',
            'items.*.quantity'  => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($attributes, $id) {
                $orderItems = OrderItem::query()
                    ->whereKey(array_column($attributes['items'], 'order_id'))
                    ->get();

                $fulfillment = Fulfillment::create([
                    'order_id'          => $id,
                    'service'           => $attributes['service'],
                    'tracking_company'  => FulfillmentCompanyEnum::tryFrom($attributes['tracking_company'])?->name ?? FulfillmentCompanyEnum::黑貓宅急便,
                    'tracking_number'   => $attributes['tracking_number'] ?? null,
                ]);

                foreach ($attributes['items'] as $item) {
                    if ($item['quantity'] <= 0)  continue;

                    $orderItem = $orderItems->where('id', $item['order_id'])->first();

                    $fulfillment->items()->create([
                        'fulfillment_id'        => $fulfillment->getKey(),
                        'order_item_id'         => $orderItem['id'],
                        'product_id'            => $orderItem['product_id'],
                        'product_name'          => $orderItem['product_name'],
                        'variant_name'          => $orderItem['variant_name'],
                        'sku'                   => $orderItem['sku'],
                        'barcode'               => $orderItem['barcode'],
                        'price'                 => $orderItem['price'],
                        'total_discount'        => $orderItem['total_discount'],
                        'fulfilled_quantity'    => $item['quantity'],
                    ]);
                }

                // 如果是手動出貨，則不需要印刷物流單
                if ($fulfillment->service === FulfillmentServiceEnum::手動出貨) return redirect()->route('orders.edit', ['order' => $id]);

                $baseUrl = match ($fulfillment->service) {
                    FulfillmentServiceEnum::黑貓宅急便 => boolval(env('CAT_TEST_MODE')) ? 'https://egs.suda.com.tw:8443/api/Egs/PrintOBT' : 'https://api.suda.com.tw/api/Egs/PrintOBT',
                    default => throw new \Exception('不支援的物流服務'),
                };

                $item = [
                    'OBTNumber' => '',
                    'OrderId' => $fulfillment->order->order_number,
                    'Thermosphere' => '0001',
                    'Spec' => '0001',
                    'ReceiptLocation' => '01',
                    'RecipientName' => $fulfillment->order->shippingAddress?->full_name,
                    'RecipientTel' => '',
                    'RecipientMobile' => $fulfillment->order->shippingAddress?->phone,
                    'RecipientAddress' => $fulfillment->order->shippingAddress?->address1,
                    'SenderName' => $fulfillment->order->location?->name,
                    'SenderTel' => '',
                    'SenderMobile' => $fulfillment->order->location?->phone,
                    'SenderZipCode' => '12-086-27-A',
                    'SenderAddress' => $fulfillment->order->location?->address1,
                    'ShipmentDate' => now()->format('Ymd'),
                    'DeliveryDate' => now()->format('Ymd'),
                    'DeliveryTime' => '04',
                    'IsFreight' => 'N',
                    'IsCollection' => 'N',
                    'CollectionAmount' => 0,
                    'IsSwipe' => 'N',
                    'IsDeclare' => 'N',
                    'DeclareAmount' => 0,
                    'ProductTypeId' => '0015',
                    'ProductName' => '商品名稱',
                    'Memo' => '',
                ];

                $response = Http::post($baseUrl, [
                    'CustomerId'        => env('CAT_CUSTOMER_ID'),
                    'CustomerToken'     => env('CAT_TOKEN'),
                    'PrintType'         => '01',
                    'PrintOBTType'      => '01',
                    'Orders'            => [$item],
                ]);

                if ($response->json('IsOK') !== 'Y') throw new \Exception($response->json('Message', '發生錯誤，請稍後再試'));

                $fulfillment->update([
                    'tracking_number' => $response['Data']['Orders'][0]['OBTNumber'] ?? null,
                    'message' => $response->body(),
                ]);
            });

            return redirect()->route('orders.edit', ['order' => $id]);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function purchase(Request $request, string $id)
    {
        Gate::authorize(PermissionNameEnum::所有訂單);

        $attributes = $request->validate([
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'remark'            => 'nullable|string',
            'items'             => 'required|array',
            'items.*.order_id'  => 'required|integer|exists:order_items,id',
            'items.*.quantity'  => 'required|integer|min:1', // TODO: 尚未限制最大數量
        ]);

        try {
            DB::transaction(function () use ($attributes, $id) {
                $order = Order::findOrFail($id);

                $prchaseOrder = PurchaseOrder::firstOrCreate(['id' => $attributes['purchase_order_id'] ?? null], [
                    'issuer_user_id'        => auth()->id(),
                    'location_id'           => $order->shipping_location_id,
                    'order_number'          => 'PO' . date('YmdHis'),
                    'type'                  => PurchaseOrderTypeEnum::進貨,
                    'remark'                => $attributes['remark'] ?? null,
                ]);

                foreach ($attributes['items'] as $item) {
                    $orderItem = $order->items()->where('id', $item['order_id'])->first();

                    $prchaseOrder->items()->create([
                        'order_item_id' => $orderItem->getKey(),
                        'product_id' => $orderItem->product_id,
                        'variant_id' => $orderItem->variant_id,
                        'product_name' => $orderItem->product_name,
                        'variant_name' => $orderItem->variant_name,
                        'sku' => $orderItem->sku,
                        'barcode' => $orderItem->barcode,
                        'quantity' => $item['quantity'],
                        'price' => $orderItem?->price ?? 0,
                        'cost_price' => $orderItem?->cost_price ?? 0,
                        'total_cost' => ($orderItem?->cost_price ?? 0) * ($item['quantity'] ?? 0),
                    ]);
                }
            });

            return redirect()->back();
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function slip(string $id)
    {
        $data = Order::findOrFail($id)->load(['items' => function ($query) {
            $query
                ->leftJoin('form_template_items', 'order_items.properties->attribute_name', '=', 'form_template_items.attribute_name')
                ->orderBy('form_template_items.position', 'asc');
        }]);

        return DomPDFHelper::render('pdf.slip', ['order' => $data])->stream('quotation.pdf');
    }

    public function preview(string $id)
    {
        $data = Order::findOrFail($id)->load(['items' => function ($query) {
            $query
                ->leftJoin('form_template_items', 'order_items.properties->attribute_name', '=', 'form_template_items.attribute_name')
                ->orderBy('form_template_items.position', 'asc');
        }]);

        return DomPDFHelper::render('pdf.preview', ['order' => $data])->stream('quotation.pdf');
    }

    public function export(Request $request)
    {
        Gate::authorize(PermissionNameEnum::所有權限);

        $filename = '訂單明細_' . now('Asia/Taipei')->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new OrderExport(), $filename);
    }
}
