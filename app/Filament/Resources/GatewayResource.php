<?php

namespace App\Filament\Resources;

use App\Enums\Gateway\GatewayTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Filament\Resources\GatewayResource\Pages;
use App\Filament\Resources\GatewayResource\RelationManagers;
use App\Models\Gateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GatewayResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::收款設定;

    protected static ?string $model = Gateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = '設定';

    protected static ?string $label = '付款方式';

    protected static ?string $pluralLabel = '收款設定';

    protected static ?int $navigationSort = 200;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Toggle::make('is_enabled')
                    ->label('啟用結帳付款方式')
                    ->default(true)
                    ->reactive()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('支付類型')
                    ->options(GatewayTypeEnum::class)
                    ->disabled(fn ($get) => !$get('is_enabled'))
                    ->afterStateUpdated(function ($get, $set, $state) {
                        $set('title', GatewayTypeEnum::tryFrom($state)?->name ?? null);
                    })
                    ->reactive()
                    ->required(fn ($get) => $get('is_enabled')),
                Forms\Components\TextInput::make('title')
                    ->label('支付名稱')
                    ->disabled(fn ($get) => !$get('is_enabled'))
                    ->required(fn ($get) => $get('is_enabled')),
                Forms\Components\Repeater::make('sub_gateways')
                    ->label('次要收款金流')
                    ->columns(3)
                    ->visible(fn ($get) => count(GatewayTypeEnum::tryFrom($get('type'))?->subGateways() ?? []))
                    ->schema([
                        Forms\Components\Select::make('gateway_method')
                            ->label('收款方式')
                            ->options(fn ($get) => array_column(GatewayTypeEnum::tryFrom($get('../../type'))?->subGateways() ?? [], 'title', 'gateway_method'))
                            ->afterStateUpdated(function ($get, $set, $state) {
                                $array = GatewayTypeEnum::tryFrom($get('../../type'))?->subGateways();

                                $title = collect($array)->firstWhere('gateway_method', $state)['title'] ?? null;

                                if (!$get('title')) $set('title', $title);
                            })
                            ->reactive()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('收款名稱')
                            ->required(),
                        Forms\Components\TextInput::make('fee_rate')
                            ->label('手續費率')
                            ->numeric()
                            ->default(0)
                            ->afterStateUpdated(function ($get, $set, $state) {
                                $set('fee_rate', $state);
                            }),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('收款方式')
                    ->description(fn ($record) => GatewayTypeEnum::tryFrom($record->type)?->value),
                Tables\Columns\TextColumn::make('is_enabled')
                    ->label('狀態')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        true => 'success',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state ? '啟用' : '停用'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageGateways::route('/'),
        ];
    }
}
