<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ManageCategories extends \SolutionForest\FilamentTree\Resources\Pages\TreePage
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTreeRecordDescription(?Model $record = null): string|HtmlString|null
    {
        return '指定商品: ' . $record->loadMissing('products')->products->count();
    }
}
