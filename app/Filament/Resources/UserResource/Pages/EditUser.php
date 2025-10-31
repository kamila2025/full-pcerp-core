<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Spatie\Permission;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
}
