<?php

namespace App\Filament\Resources;

use App\Enums\PermissionNameEnum;
use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Spatie\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::角色權限;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationGroup = '設定';

    protected static ?string $navigationLabel = '角色設定';

    protected static ?int $navigationSort = 900;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                ->label('角色')
                ->unique(ignoreRecord: true)
                ->required(),
            Forms\Components\Section::make('permission')
                ->heading('權限')
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->hiddenLabel()
                        ->options(PermissionNameEnum::class)
                        ->dehydrated(false)
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record?->getPermissionNames() ?? []))
                        ->bulkToggleable()
                        ->selectAllAction(fn ($action) => $action->label('全選'))
                        ->deselectAllAction(fn ($action) => $action->label('取消全選'))
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label('角色')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('guard_name')
                ->label('守衛名稱')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('permissions.name')
                ->label('權限')
                ->badge()
                ->formatStateUsing(fn ($state) => array_column(PermissionNameEnum::cases(), 'name', 'value')[$state]),

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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
