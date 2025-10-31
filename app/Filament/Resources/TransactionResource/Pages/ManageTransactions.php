<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Enums\Transaction\TransactionStatusEnum;
use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageTransactions extends ManageRecords
{
    protected static string $resource = TransactionResource::class;

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
                ->label('處理中')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::處理中)),
            'inactive' => Tab::make()
                ->label('已付款')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::已付款)),
            'inactive' => Tab::make()
                ->label('失敗')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::失敗)),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'active';
    }
}
