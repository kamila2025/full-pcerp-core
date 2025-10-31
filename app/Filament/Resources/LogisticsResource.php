<?php

namespace App\Filament\Resources;

use App\Enums\Logistics\LogisticsProviderEnum;
use App\Enums\Logistics\LogisticsTypeEnum;
use App\Enums\PermissionNameEnum;
use App\Filament\Resources\LogisticsResource\Pages;
use App\Filament\Resources\LogisticsResource\RelationManagers;
use App\Models\Logistics;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogisticsResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::物流設定;

    protected static ?string $model = Logistics::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = '設定';

    protected static ?string $label = '物流';

    protected static ?string $pluralLabel = '物流設定';

    protected static ?int $navigationSort = 400;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Toggle::make('is_enabled')
                    ->label('啟用物流方式')
                    ->default(true)
                    ->reactive()
                    ->required(),
                Forms\Components\Select::make('provider')
                    ->label('物流供應商')
                    ->options(LogisticsProviderEnum::class)
                    ->disabled(fn ($get) => !$get('is_enabled'))
                    ->required(fn ($get) => $get('is_enabled')),
                Forms\Components\Select::make('type')
                    ->label('物流類型')
                    ->options(LogisticsTypeEnum::class)
                    ->disabled(fn ($get) => !$get('is_enabled'))
                    ->afterStateUpdated(function ($get, $set, $state) {
                        $set('name', LogisticsTypeEnum::tryFrom($state)?->name ?? null);
                    })
                    ->reactive()
                    ->required(fn ($get) => $get('is_enabled')),
                Forms\Components\TextInput::make('name')
                    ->label('物流名稱')
                    ->disabled(fn ($get) => !$get('is_enabled'))
                    ->required(fn ($get) => $get('is_enabled')),
                Forms\Components\TextInput::make('fee')
                    ->label('運費')
                    ->numeric()
                    ->disabled(fn ($get) => !$get('is_enabled'))
                    ->required(fn ($get) => $get('is_enabled')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->label('物流供應商')
                    ->state(fn ($record) => $record->provider?->name),
                Tables\Columns\TextColumn::make('type')
                    ->label('物流類型')
                    ->state(fn ($record) => $record->type?->name),
                Tables\Columns\TextColumn::make('name')
                    ->label('物流名稱'),
                Tables\Columns\TextColumn::make('fee')
                    ->label('運費'),
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
            'index' => Pages\ManageLogistics::route('/'),
        ];
    }
}
