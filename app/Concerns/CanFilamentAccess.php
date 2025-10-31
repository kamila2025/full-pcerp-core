<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait CanFilamentAccess
{
    public static function getPermissionId()
    {
        if (property_exists(__CLASS__, 'permissionId')) return static::$permissionId;

        return null;
    }

    public function mountCanFilamentAccess(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        return Gate::forUser(auth()->user())->check(static::getPermissionId());
    }

    public static function can(string $action, ?Model $record = null): bool
    {
        return Gate::forUser(auth()->user())->check(static::getPermissionId());
    }
}
