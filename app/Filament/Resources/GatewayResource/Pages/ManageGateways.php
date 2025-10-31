<?php

namespace App\Filament\Resources\GatewayResource\Pages;

use App\Filament\Resources\GatewayResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGateways extends ManageRecords
{
    protected static string $resource = GatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
