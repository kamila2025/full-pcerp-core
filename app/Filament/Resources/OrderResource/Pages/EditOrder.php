<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Events\OrderSavedFinancialStatus;
use App\Events\OrderSavedFulfillmentStatus;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        event(new OrderSavedFinancialStatus($this->getRecord()));

        event(new OrderSavedFulfillmentStatus($this->getRecord()));

        $this->fillForm();
    }
}
