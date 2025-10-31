<?php

namespace App\Filament\Resources;

use App\Enums\PermissionNameEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Spatie\Permission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class UserResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::員工設定;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $modelLabel = '員工';

    protected static ?string $navigationGroup = '設定';

    protected static ?string $navigationLabel = '員工設定';

    protected static ?int $navigationSort = 700;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('帳號')
                            ->hint('視同為登入帳號')
                            ->disabled(fn ($record) => $record && !filter_var($record?->email, FILTER_VALIDATE_EMAIL) || $record?->id === auth()->id())
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->label('密碼')
                            ->hint('視同為登入密碼, 留空則不變更密碼')
                            ->password()
                            ->disabled(fn ($record) => $record && !filter_var($record?->email, FILTER_VALIDATE_EMAIL) || $record?->id === auth()->id())
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ]),
                Forms\Components\Section::make('roles')
                    ->heading('角色')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->hiddenLabel()
                            ->lazy()
                            ->relationship(name: 'roles', titleAttribute: 'name')
                    ]),
                Forms\Components\Section::make('locations')
                    ->heading('地點')
                    ->schema([
                        Forms\Components\CheckboxList::make('locations')
                            ->hiddenLabel()
                            ->lazy()
                            ->relationship(name: 'locations', titleAttribute: 'name')
                    ]),
                Forms\Components\Section::make('permission')
                    ->heading('權限')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->hiddenLabel()
                            ->options(PermissionNameEnum::class)
                            ->dehydrated(false)
                            ->descriptions(fn ($get) => Permission::whereHas('roles', fn ($query) => $query->whereKey($get('roles')))->pluck('name')->mapWithKeys(fn ($name) => [$name => new HtmlString('<span class="text-danger-600">* 權限已擁有</span>')])->toArray())
                            ->disableOptionWhen(fn ($get, $value): bool => in_array($value, Permission::whereHas('roles', fn ($query) => $query->whereKey($get('roles')))->pluck('name')->toArray()))
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->color(fn ($record) => $record->trashed() ? 'danger' : null)
                    ->formatStateUsing(fn ($state, $record) => $record->trashed() ? "<span style='text-decoration: line-through;'>{$state}</span>" : $state)
                    ->html(),
                Tables\Columns\TextColumn::make('email')
                    ->label('帳號')
                    ->searchable()
                    ->color(fn ($record) => $record->trashed() ? 'danger' : null)
                    ->formatStateUsing(fn ($state, $record) => $record->trashed() ? "<span style='text-decoration: line-through;'>{$state}</span>" : $state)
                    ->html(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge(),
                Tables\Columns\TextColumn::make('locations.name')
                    ->label('地點')
                    ->badge(),
                Tables\Columns\TextColumn::make('permissions.name')
                    ->label('權限')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->formatStateUsing(fn ($state) => array_column(PermissionNameEnum::cases(), 'name', 'value')[$state]),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('狀態')
                    ->badge()
                    ->state(fn ($record) => match (true) {
                        $record->trashed() => '已刪除',
                        boolval($record->email_verified_at) => '啟用',
                        !boolval($record->email_verified_at) => '邀請中',
                        default => '未知',
                    })
                    ->color(fn ($record) => match (true) {
                        $record->trashed() => 'danger',
                        boolval($record->email_verified_at) => 'success',
                        !boolval($record->email_verified_at) => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('刪除時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->id === auth()->id() || $record->trashed()),
                Tables\Actions\RestoreAction::make()
                    ->hidden(fn ($record) => !$record->trashed()),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record->id !== auth()->id() || $record->id === 1;
    }

    public static function canDelete(Model $record): bool
    {
        return $record->id !== auth()->id();
    }
}
