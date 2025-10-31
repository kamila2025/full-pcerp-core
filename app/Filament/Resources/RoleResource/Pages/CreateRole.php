<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Spatie\Permission;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public $permissions = [];

    public function beforeCreate()
    {
        $data = $this->form->getRawState();

        $this->permissions = $data['permissions'];

        array_map(fn ($permission) => Permission::findOrCreate($permission), collect()->wrap($this->permissions)->toArray());

        unset($data['permissions']);
    }

    public function afterCreate()
    {
        $this->record->syncPermissions($this->permissions);
    }
}
