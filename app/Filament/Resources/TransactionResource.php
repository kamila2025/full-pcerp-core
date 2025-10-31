<?php

namespace App\Filament\Resources;

use App\Enums\Gateway\GatewayTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Location;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class TransactionResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::收款明細;

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationGroup = '訂單管理';

    protected static ?string $label = '訂單';

    protected static ?string $pluralLabel = '收款明細';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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
                    ->description(fn ($record) => $record->order?->attributionUser?->name)
                    ->url(fn ($record) => route('orders.edit', $record->order_id)),
                Tables\Columns\TextColumn::make('order.customer.name')
                    ->label('顧客'),
                Tables\Columns\TextColumn::make('gateway_title')
                    ->label('方式'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('金額')
                    ->numeric()
                    ->description(fn ($record) => match ($record->gateway_type) {
                        GatewayTypeEnum::銀行轉帳 => "匯款帳號: $record->reference",
                        GatewayTypeEnum::分期付款 => "期數: {$record->additional['period']}, 月付: {$record->additional['amount']}",
                        default => '',
                    }),
                Tables\Columns\TextColumn::make('note')
                    ->label('備註'),
                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->state(fn ($record) => match ($record->status) {
                        TransactionStatusEnum::處理中 => '處理中',
                        TransactionStatusEnum::已付款 => '已付款',
                        TransactionStatusEnum::失敗 => '失敗',
                        default => '未知',
                    })
                    ->icon(fn ($record) => match ($record->status) {
                        TransactionStatusEnum::處理中 => 'heroicon-o-clock',
                        TransactionStatusEnum::已付款 => 'heroicon-s-check-circle',
                        TransactionStatusEnum::失敗 => 'heroicon-s-x-circle',
                        default => 'heroicon-s-x-circle',
                    })
                    ->color(fn ($record) => match ($record->status) {
                        TransactionStatusEnum::處理中 => 'info',
                        TransactionStatusEnum::已付款 => 'success',
                        TransactionStatusEnum::失敗 => 'danger',
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
                Tables\Filters\SelectFilter::make('gateway_type')
                    ->label('類型')
                    ->options(GatewayTypeEnum::class)
                    ->multiple()
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
                Tables\Actions\Action::make('confirm')
                    ->label('確認付款')
                    ->icon('heroicon-s-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record) {
                        $record->update([
                            'status' => TransactionStatusEnum::已付款,
                        ]);
                    })
                    ->hidden(fn ($record) => $record->status !== TransactionStatusEnum::處理中),
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status === TransactionStatusEnum::已付款),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransactions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
