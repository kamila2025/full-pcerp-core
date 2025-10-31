<?php

namespace App\Filament\Resources;

use App\Enums\Address\AddressTypeEnum;
use App\Enums\Fulfillment\FulfillmentStatusEnum;
use App\Enums\Order\OrderDeliveryTypeEnum;
use App\Enums\Order\OrderFinancialStatusEnum;
use App\Enums\Order\OrderFulfillmentStatusEnum;
use App\Enums\Order\OrderSourceTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    protected static $permissionId = PermissionNameEnum::所有訂單;

    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = '訂單管理';

    protected static ?string $label = '訂單';

    protected static ?string $pluralLabel = '所有訂單';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->extraAttributes([
                'x-data' =>
                <<<HTML
                    {
                        data: \$wire.entangle('data'),
                        calculateSubtotal() {
                            for (const key in this.data.items) {
                                const item = this.data.items[key];

                                item.total_amount = parseInt(item.price || 0) * parseInt(item.quantity || 0);
                            }

                            this.data.subtotal_price = Object.values(this.data.items).reduce((sum, item) => sum + parseInt(item.total_amount || 0), 0);

                            this.data.amount = parseInt(this.data.subtotal_price) + parseInt(this.data.total_shipping);
                        },
                        init() {
                            this.calculateSubtotal();

                            \$watch('data.items', () => this.calculateSubtotal());
                        },
                    }
                HTML,
            ])
            ->schema([
                Forms\Components\Hidden::make('source_type')
                    ->default(OrderSourceTypeEnum::後台),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Placeholder::make('amount')
                                    ->label('訂單總金額')
                                    ->key('amount')
                                    // $record?->amount
                                    // ->content(new HtmlString('<div class="font-bold" x-html="`${data.amount} TWD`"></div>'))
                                    ->content(fn ($record) => new HtmlString("<div class=\"font-bold\">{$record?->amount} TWD</div>"))
                                    ->helperText(fn ($record) => <<<HTML
                                       {$record?->transactions->where('status', TransactionStatusEnum::已付款)->sum('amount')} 已付款，
                                       {$record?->transactions->where('status', TransactionStatusEnum::處理中)->sum('amount')} 待確認
                                    HTML)
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('payment')
                                            ->label('付款')
                                            ->modalHeading('結帳設定')
                                            ->form([
                                                Forms\Components\Select::make('gateway_type')
                                                    ->label('付款方式')
                                                    ->options(\App\Models\Gateway::where('is_enabled', true)->pluck('title', 'type'))
                                                    ->required(),
                                                Forms\Components\TextInput::make('paid_amount')
                                                    ->label('金額')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ])
                                            ->action(function ($record, $data, $livewire) {
                                                try {
                                                    \App\Models\Transaction::create([
                                                        'order_id'      => $record->getKey(),
                                                        'gateway_type'  => $data['gateway_type'],
                                                        'amount'        => $data['paid_amount'],
                                                    ]);

                                                    Notification::make()
                                                        ->success()
                                                        ->title('付款成功');

                                                    $livewire->mount($record->getKey());
                                                } catch (\Throwable $e) {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('付款失敗')
                                                        ->body($e->getMessage())
                                                        ->send();
                                                }
                                            })
                                            ->hidden(fn ($record) => $record?->financial_status === OrderFinancialStatusEnum::已付款),
                                    ),
                                Forms\Components\Placeholder::make('count')
                                    ->label('商品總數')
                                    ->key('count')
                                    ->content(fn ($record) => new HtmlString("<div class=\"font-bold\">{$record?->items->sum('quantity')}</div>"))
                                    ->helperText(fn ($record) => <<<HTML
                                        {$record?->fulfilledItems()->whereRelation('fulfillment', 'status', FulfillmentStatusEnum::已出貨)->sum('fulfilled_quantity')} 已出貨，
                                        {$record?->fulfilledItems()->whereRelation('fulfillment', 'status', FulfillmentStatusEnum::待出貨)->sum('fulfilled_quantity')} 待確認
                                    HTML)
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('ship')
                                            ->label('出貨')
                                            ->modalHeading('出貨資訊')
                                            ->mountUsing(function (Forms\ComponentContainer $form, Order $record) {
                                                $data = $record->load([
                                                    'items' => function ($query) {
                                                        $query->whereRaw('quantity > (
                                                            SELECT COALESCE(SUM(fulfilled_quantity), 0)
                                                            FROM fulfillment_items
                                                            WHERE fulfillment_items.order_item_id = order_items.id
                                                        )');
                                                    },
                                                    'items.fulfillmentItems',
                                                ]);

                                                $form->fill($data->toArray());
                                            })
                                            ->form([
                                                TableRepeater::make('items')
                                                    ->hiddenLabel()
                                                    ->addable(false)
                                                    ->deletable(false)
                                                    ->reorderable(false)
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('商品項目')
                                                            ->content(fn ($record, $get) => str($get('product_name'))->limit(30))
                                                            ->helperText(fn ($record, $get) => collect($get('variant.values'))->pluck('name')->join('|')),
                                                        Forms\Components\TextInput::make('fulfilled_quantity')
                                                            ->label('出貨數量')
                                                            ->formatStateUsing(fn ($record, $get) => $get('quantity') - array_sum(array_column($get('fulfillment_items') ?? [], 'fulfilled_quantity')))
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required(),
                                                    ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->action(function (Order $record, $data, $livewire) {
                                                try {
                                                    DB::beginTransaction();

                                                    $fulfillment = \App\Models\Fulfillment::create([
                                                        'order_id' => $record->getKey(),
                                                    ]);

                                                    foreach ($data['items'] as $item) {
                                                        \App\Models\FulfillmentItem::create([
                                                            'fulfillment_id'        => $fulfillment->getKey(),
                                                            'order_item_id'         => $item['id'],
                                                            'product_id'            => $item['product_id'],
                                                            'product_name'          => $item['product_name'],
                                                            'variant_name'          => $item['variant_name'],
                                                            'fulfilled_quantity'    => $item['fulfilled_quantity'],
                                                        ]);
                                                    }
                                                    Notification::make()
                                                        ->success()
                                                        ->title('出貨成功')
                                                        ->send();

                                                    $livewire->mount($record->getKey());

                                                    DB::commit();
                                                } catch (\Throwable $e) {
                                                    DB::rollBack();

                                                    Notification::make()
                                                        ->danger()
                                                        ->title('出貨失敗')
                                                        ->body($e->getMessage())
                                                        ->send();
                                                }
                                            })
                                            ->hidden(fn ($record) => $record?->fulfillment_status === OrderFulfillmentStatusEnum::已出貨),
                                    ),
                                Forms\Components\Select::make('financial_status')
                                    ->label('付款狀態')
                                    ->disabled()
                                    ->options(OrderFinancialStatusEnum::class)
                                    ->nullable(),
                                Forms\Components\Select::make('fulfillment_status')
                                    ->label('出貨狀態')
                                    ->options(OrderFulfillmentStatusEnum::class)
                                    ->disabled(),
                            ])
                            ->hiddenOn('create')
                            ->columns(2)
                            ->columnSpan(['lg' => 9]),
                        Forms\Components\Section::make()
                            ->heading(fn ($livewire) => $livewire instanceof Pages\CreateOrder ? '訂單內容' : '')
                            ->schema([
                                static::makeItemRepeater('items'),
                                Forms\Components\TextInput::make('subtotal_price')
                                    ->label('小計')
                                    ->inlineLabel()
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAlpineAttributes(['x-model' => 'data.subtotal_price'])
                                    ->columnStart(['default' => 1, 'lg' => 2]),
                                Forms\Components\TextInput::make('total_discount')
                                    ->label('折扣')
                                    ->inlineLabel()
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAlpineAttributes(['x-model' => 'data.total_discount'])
                                    ->columnStart(['default' => 1, 'lg' => 2]),
                                Forms\Components\TextInput::make('total_shipping')
                                    ->label('運費')
                                    ->inlineLabel()
                                    ->numeric()
                                    ->default(0)
                                    ->extraAlpineAttributes(['x-model' => 'data.total_shipping'])
                                    ->columnStart(['default' => 1, 'lg' => 2])
                                    ->nullable(),
                                Forms\Components\TextInput::make('total_tax')
                                    ->label('稅金')
                                    ->inlineLabel()
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAlpineAttributes(['x-model' => 'data.total_tax'])
                                    ->columnStart(['default' => 1, 'lg' => 2]),
                                Forms\Components\TextInput::make('amount')
                                    ->label('總額')
                                    ->inlineLabel()
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAlpineAttributes(['x-model' => 'data.amount'])
                                    ->columnStart(['default' => 1, 'lg' => 2]),
                                Forms\Components\Textarea::make('remark')
                                    ->label('顧客備註欄')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpan(['lg' => 9]),
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Textarea::make('note')
                                    ->label('商家備註')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['lg' => 9]),
                    ])
                    ->columnSpan(['lg' => 9]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('訂單狀態')
                                    ->disabled()
                                    ->options(OrderStatusEnum::class)
                                    ->nullable(),
                            ])
                            ->hiddenOn('create'),

                        self::makeCustomerSection(),
                        ...self::makeDeliverySections(),
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('創建於')
                                    ->content(fn ($record): string => $record ? $record->created_at->diffForHumans() : '-')
                                    ->helperText(fn ($record): string => $record ? $record->created_at->format('Y-m-d H:i:s') : '-'),
                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('最後更新於')
                                    ->content(fn ($record): string => $record ? $record->updated_at->diffForHumans() : '-')
                                    ->helperText(fn ($record): string => $record ? $record->updated_at->format('Y-m-d H:i:s') : '-'),
                            ])
                            ->hiddenOn('create'),
                    ])
                    ->columnSpan(['lg' => 3]),
            ])
            ->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('訂單編號')
                    ->icon(fn ($record) => match ($record->source_type) {
                        OrderSourceTypeEnum::後台->value => 'heroicon-o-pencil-square',
                        OrderSourceTypeEnum::商店->value => 'heroicon-o-building-storefront',
                        OrderSourceTypeEnum::ERP->value => 'heroicon-o-document-text',
                        OrderSourceTypeEnum::POS->value => 'heroicon-o-currency-yen',
                        default => 'heroicon-o-information-circle',
                    })
                    ->disabledClick()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('訂單日期')
                    ->sortable()
                    ->state(fn ($record) => Carbon::parse($record->created_at)->diffForHumans()),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('客戶姓名')
                    ->state(fn ($record) => $record->customer?->name ?? $record->full_name)
                    ->url(fn ($record) => $record->customer_id ? CustomerResource::getUrl('edit', ['record' => $record->customer_id]) : null)
                    ->color(fn ($record) => $record->customer ? 'warning' : null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('financial_status')
                    ->label('付款狀態')
                    ->badge()
                    ->state(fn ($record) => $record->financial_status?->name)
                    ->icon(fn ($record) => match ($record->financial_status) {
                        OrderFinancialStatusEnum::已付款 => 'heroicon-o-check-circle',
                        default => 'heroicon-o-exclamation-circle',
                    })
                    ->color(fn ($record) => match ($record->financial_status) {
                        OrderFinancialStatusEnum::已付款 => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('fulfillment_status')
                    ->label('出貨狀態')
                    ->badge()
                    ->state(fn ($record) => $record->fulfillment_status?->name)
                    ->icon(fn ($record) => match ($record->fulfillment_status) {
                        OrderFulfillmentStatusEnum::已出貨 => 'heroicon-o-truck',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn ($record) => match ($record->fulfillment_status) {
                        OrderFulfillmentStatusEnum::已出貨 => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('總金額')
                    ->money()
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    protected static function makeItemRepeater($name): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('items')
            ->addActionLabel('新增其他商品')
            ->relationship()
            ->defaultItems(1)
            ->hiddenLabel()
            ->schema([
                Forms\Components\Hidden::make('variant_id'),
                Forms\Components\TextInput::make('product_name')
                    ->label('產品')
                    ->disabled(fn (Forms\Get $get, Forms\Set $set) => $get('variant_id'))
                    ->dehydrated()
                    ->prefixAction(
                        Forms\Components\Actions\Action::make('product')
                            ->label('選擇商品')
                            ->icon('heroicon-o-bars-arrow-up')
                            ->form([
                                Forms\Components\Select::make('variant_id')
                                    ->hiddenLabel()
                                    ->relationship(name: 'variant')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->product->name}")
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpanFull(),
                            ])
                            ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                                $variant = \App\Models\Variant::findOrFail($data['variant_id']);

                                $set('variant_id', $variant->id);

                                $set('product_name', $variant->product->name);

                                $set('price', intval($variant->price));
                            }),
                    )
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('product-x')
                            ->icon('heroicon-o-x-mark')
                            ->hidden(fn ($get) => $get('product_id') === null)
                            ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                                $set('product_id', null);

                                $set('product_name', null);

                                $set('../../subtotal', collect($get('items'))->sum(fn ($item) => intval($item['price']) * intval($item['quantity'])));

                                $set('../../amount', intval($get('../../subtotal')) + intval($get('../../tax')) + intval($get('../../shipping')) + intval($get('../../service_fee')) + intval($get('../../transaction_fee')) - intval($get('../../discount')));
                            }),
                    )
                    ->columnSpan(['lg' => 4])
                    ->nullable(),
                Forms\Components\TextInput::make('price')
                    ->label('單價')
                    ->numeric()
                    ->default(50)
                    ->disabled(fn (Forms\Get $get) => $get('variant_id'))
                    ->dehydrated()
                    ->columnSpan(['lg' => 2])
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('數量')
                    ->numeric()
                    ->default(1)
                    ->columnSpan(['lg' => 2])
                    ->hint(fn ($record) => '出貨 ' . $record?->fulfillmentItems->sum('fulfilled_quantity'))
                    ->required(),
                Forms\Components\TextInput::make('total_amount')
                    ->label('小計')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(['lg' => 2])
                    ->required(),
            ])
            ->columns(10)
            ->columnSpanFull()
            ->required();
    }

    protected static function makeCustomerSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make()
            ->heading('顧客')
            ->headerActions([
                Forms\Components\Actions\Action::make('customer')
                    ->label('選擇現有顧客')
                    ->hidden(fn ($get) => $get('customer_id'))
                    ->iconButton()
                    ->icon('heroicon-o-bars-arrow-up')
                    ->form([
                        Forms\Components\Select::make('customer_id')
                            ->hiddenLabel()
                            ->relationship('customer', 'name')
                    ])
                    ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                        $customer = \App\Models\Customer::findOrFail($data['customer_id']);

                        $set('customer_id', $customer->id);

                        $set('full_name', $customer->name);

                        $set('email', $customer->email);

                        $set('phone', $customer->phone);
                    }),
                Forms\Components\Actions\Action::make('clear_customer')
                    ->hidden(fn ($get) => !$get('customer_id'))
                    ->iconButton()
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->modalHeading('確認清除顧客資料')
                    ->modalDescription('您確定要清除顧客資料嗎？此操作無法復原。')
                    ->modalSubmitActionLabel('清除')
                    ->action(function (Forms\Set $set) {
                        $set('customer_id', null);

                        $set('full_name', null);

                        $set('email', null);

                        $set('phone', null);
                    }),
            ])
            ->schema([
                Forms\Components\Hidden::make('customer_id'),
                Forms\Components\TextInput::make('full_name')
                    ->label('姓名')
                    ->disabled(fn ($get) => $get('customer_id'))
                    ->dehydrated(),
                Forms\Components\TextInput::make('email')
                    ->label('信箱')
                    ->email()
                    ->disabled(fn ($get) => $get('customer_id'))
                    ->dehydrated(),
                Forms\Components\TextInput::make('phone')
                    ->label('電話')
                    ->tel()
                    ->disabled(fn ($get) => $get('customer_id'))
                    ->dehydrated(),
            ]);
    }

    protected static function makeDeliveryActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('recipient_address')
                ->label('宅配')
                ->iconButton()
                ->icon('heroicon-o-truck')
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('full_name')
                                ->label('收件人姓名')
                                ->required(),
                            Forms\Components\TextInput::make('company')
                                ->label('公司名稱'),
                            Forms\Components\TextInput::make('address1')
                                ->label('收件地址')
                                ->columnSpanFull()
                                ->required(),
                            Forms\Components\TextInput::make('email')
                                ->label('電子郵件地址'),
                            Forms\Components\TextInput::make('phone')
                                ->label('電話')
                                ->tel()
                                ->required(),
                        ])
                ])
                ->action(function ($data, $set, $action, $record) {
                    $record?->pickupAddress()?->delete();

                    $set('delivery_type', OrderDeliveryTypeEnum::運送);

                    $set('shippingAddress.address_type', AddressTypeEnum::訂單配送);

                    $set('shippingAddress.full_name', $data['full_name']);

                    $set('shippingAddress.company', $data['company']);

                    $set('shippingAddress.address1', $data['address1']);

                    $set('shippingAddress.email', $data['email']);

                    $set('shippingAddress.phone', $data['phone']);

                    $action->successNotificationTitle('收件地址已更新')->success();
                }),
            Forms\Components\Actions\Action::make('pickup_address')
                ->label('自取')
                ->iconButton()
                ->icon('heroicon-o-shopping-bag') // Icon for 自取
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('location_id')
                                ->label('地址')
                                ->columnSpanFull()
                                ->relationship('location', 'name')
                                ->required(),
                            Forms\Components\TextInput::make('full_name')
                                ->label('收件人姓名')
                                ->columnSpanFull()
                                ->required(),
                            Forms\Components\TextInput::make('email')
                                ->label('電子郵件地址'),
                            Forms\Components\TextInput::make('phone')
                                ->label('電話'),
                        ]),
                ])
                ->action(function ($data, $set, $action, $record) {
                    $record?->shippingAddress()?->delete();

                    $set('delivery_type', OrderDeliveryTypeEnum::自取);

                    $set('pickupAddress.address_type', \App\Enums\Address\AddressTypeEnum::訂單自取);

                    $set('pickupAddress.location_id', $data['location_id']);

                    $set('pickupAddress.full_name', $data['full_name']);

                    $set('pickupAddress.email', $data['email']);

                    $set('pickupAddress.phone', $data['phone']);

                    $action->successNotificationTitle('自取地點已更新')->success();
                }),
        ];
    }

    protected static function makeDeliverySections(): array
    {
        return [
            Forms\Components\Section::make()
                ->heading('收件 / 取貨')
                ->key('delivery')
                ->headerActions(self::makeDeliveryActions())
                ->schema([]),
            Forms\Components\Hidden::make('delivery_type'),
            Forms\Components\Section::make()
                ->heading('宅配地址')
                ->relationship('shippingAddress')
                ->schema([
                    Forms\Components\Hidden::make('address_type'),
                    Forms\Components\Placeholder::make('full_name')
                        ->label('姓名')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get, $record) => $get('full_name')),
                    Forms\Components\Placeholder::make('company')
                        ->label('公司')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('company')),
                    Forms\Components\Placeholder::make('address1')
                        ->label('地址')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('address1')),
                    Forms\Components\Placeholder::make('email')
                        ->label('信箱')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('email')),
                    Forms\Components\Placeholder::make('phone')
                        ->label('電話')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('phone')),
                ])
                ->hidden(fn ($get) => ($get('delivery_type')?->value ?? $get('delivery_type')) !== OrderDeliveryTypeEnum::運送->value),
            Forms\Components\Section::make()
                ->heading('取貨地址')
                ->relationship('pickupAddress')
                ->schema([
                    Forms\Components\Hidden::make('address_type'),
                    Forms\Components\Placeholder::make('location_id')
                        ->label('地址')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => \App\Models\Location::find($get('location_id'))?->name),
                    Forms\Components\Placeholder::make('full_name')
                        ->label('姓名')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('full_name')),
                    Forms\Components\Placeholder::make('email')
                        ->label('信箱')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('email')),
                    Forms\Components\Placeholder::make('phone')
                        ->label('電話')
                        ->inlineLabel()
                        ->dehydrated()
                        ->content(fn ($get) => $get('phone')),
                ])
                ->hidden(fn ($get) => ($get('delivery_type')?->value ?? $get('delivery_type')) !== OrderDeliveryTypeEnum::自取->value),
        ];
    }

    public static function canViewAny(): bool
    {
        return false;
    }
}
