<?php

namespace App\Filament\Resources;

use App\Enums\PermissionNameEnum;
use App\Filament\Resources\FormTemplateResource\Pages;
use App\Filament\Resources\FormTemplateResource\RelationManagers;
use App\Models\FormTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormTemplateResource extends Resource
{
    use \App\Concerns\CanFilamentAccess;

    protected static $permissionId = PermissionNameEnum::模板設定;

    protected static ?string $model = FormTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationGroup = '設定';

    protected static ?string $label = '模板';

    protected static ?string $pluralLabel = '模板設定';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('模板名稱')
                    ->placeholder('請輸入模板名稱')
                    ->required(),
                Forms\Components\TextInput::make('icon')
                    ->label('模板圖示')
                    ->placeholder('請輸入模板圖示')
                    ->nullable(),
                Forms\Components\Textarea::make('description')
                    ->label('模板說明')
                    ->rows(3)
                    ->placeholder('請輸入模板說明')
                    ->columnSpanFull()
                    ->nullable(),
                Forms\Components\Repeater::make('items')
                    ->label('自訂欄位')
                    ->relationship()
                    ->hiddenLabel()
                    ->orderColumn('position')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('分類')
                            ->relationship('category', 'name')
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                if ($get('attribute_name')) return;

                                $set('attribute_name', \App\Models\Category::find($state)?->name ?? '');
                            }),
                        Forms\Components\TextInput::make('attribute_name')
                            ->label('欄位名稱')
                            ->required(),
                        // Forms\Components\Select::make('default_value')
                        //     ->label('預設值')
                        //     ->relationship('product', 'name'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('模板名稱'),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('規格數量'),
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
            'index' => Pages\ListFormTemplates::route('/'),
            'create' => Pages\CreateFormTemplate::route('/create'),
            'edit' => Pages\EditFormTemplate::route('/{record}/edit'),
        ];
    }
}
