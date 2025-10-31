<?php

namespace App\Filament\Resources;

use App\Enums\Fulfillment\FulfillmentStatusEnum;
use App\Enums\PermissionNameEnum;
use App\Filament\Resources\FulfillmentResource\Pages;
use App\Filament\Resources\FulfillmentResource\RelationManagers;
use App\Models\Fulfillment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FulfillmentResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::出貨明細;

    protected static ?string $model = Fulfillment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationGroup = '訂單管理';

    protected static ?string $label = '訂單';

    protected static ?string $pluralLabel = '出貨明細';

    protected static ?int $navigationSort = 300;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('狀態')
                    ->options(FulfillmentStatusEnum::class)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('訂單編號')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition('after')
                    ->url(fn ($record) => route('orders.edit', $record->order_id)),
                Tables\Columns\TextColumn::make('order.customer.name')
                    ->label('顧客'),
                Tables\Columns\TextColumn::make('service')
                    ->label('服務')
                    ->state(fn ($record) => $record->service?->name)
                    ->description(fn ($record) => $record->tracking_company),
                Tables\Columns\TextColumn::make('items')
                    ->label('明細')
                    ->formatStateUsing(function ($record) {
                        if ($record->items->count() === 0) return '<span>無訂單明細</span>';

                        $itemsHtml = '<ul style="padding-left: 15px; margin: 0;">';

                        foreach ($record->items as $item) {
                            $itemName = str($item['product_name'] ?? '未知品項')->limit(30);
                            $itemQuantity = $item['fulfilled_quantity'] ?? null;
                            $itemPrice = isset($item['price']) ? number_format($item['price'], 0) : '未知價格';

                            $itemsHtml .= "<li style='margin-bottom: 5px;'>
                                <strong>{$itemName}</strong> × {$itemQuantity}
                            </li>";
                        }
                        $itemsHtml .= '</ul>';

                        return str($itemsHtml)->toHtmlString();
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->state(fn ($record) => $record->status?->name)
                    ->icon(fn ($record) => match ($record->status) {
                        FulfillmentStatusEnum::待辦, FulfillmentStatusEnum::待出貨 => 'heroicon-o-clock',
                        FulfillmentStatusEnum::已出貨 => 'heroicon-s-truck',
                        FulfillmentStatusEnum::已送達 => 'heroicon-s-check-circle',
                        default => 'heroicon-s-x-circle',
                    })
                    ->color(fn ($record) => match ($record->status) {
                        FulfillmentStatusEnum::待辦, FulfillmentStatusEnum::待出貨 => 'warning',
                        FulfillmentStatusEnum::已出貨 => 'info',
                        FulfillmentStatusEnum::已送達 => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('日期')
                    ->dateTime('d/m/Y h:iA')
                    ->description(fn ($record): string => Carbon::parse($record->created_at)->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('地點')
                    ->relationship('order.location', 'name')
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('開始日期'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('結束日期'),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $query
                            ->when($data['created_from'], fn (Builder $query, $date): Builder => $query->where('created_at', '>=', $date))
                            ->when($data['created_until'], fn (Builder $query, $date): Builder => $query->where('created_at', '<=', $date))
                    )
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('ship')
                    ->label('出貨')
                    ->icon('heroicon-s-truck')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => FulfillmentStatusEnum::已出貨,
                        ]);
                    })
                    ->hidden(fn ($record) => $record->status === FulfillmentStatusEnum::已出貨),
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status === FulfillmentStatusEnum::已出貨),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFulfillments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
