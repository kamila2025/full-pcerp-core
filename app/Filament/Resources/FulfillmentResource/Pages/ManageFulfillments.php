<?php

namespace App\Filament\Resources\FulfillmentResource\Pages;

use App\Enums\Fulfillment\FulfillmentStatusEnum;
use App\Filament\Resources\FulfillmentResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageFulfillments extends ManageRecords
{
    protected static string $resource = FulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('全部'),
            'active' => Tab::make()
                ->label('待出貨')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FulfillmentStatusEnum::待出貨)),
            'inactive' => Tab::make()
                ->label('已出貨')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FulfillmentStatusEnum::已出貨)),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'active';
    }
}
