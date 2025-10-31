<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Spatie\Permission;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public $permissions = [];

    public function beforeSave()
    {
        $data = $this->form->getRawState();

        $this->permissions = $data['permissions'];

        array_map(fn ($permission) => Permission::findOrCreate($permission), collect()->wrap($this->permissions)->toArray());

        unset($data['permissions']);
    }

    public function afterSave()
    {
        $this->record->syncPermissions($this->permissions);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
