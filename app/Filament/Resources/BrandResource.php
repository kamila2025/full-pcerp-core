<?php

namespace App\Filament\Resources;

use App\Enums\PermissionNameEnum;
use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Tags\Tag;

class BrandResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::商品品牌;

    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationParentItem = '商品分類';

    protected static ?string $navigationGroup = '商品管理';

    protected static ?string $label = '品牌';

    protected static ?string $pluralLabel = '商品品牌';

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
            ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'brands'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('標題'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageBrands::route('/'),
        ];
    }
}
