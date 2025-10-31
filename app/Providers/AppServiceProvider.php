<?php

namespace App\Providers;

use App\Enums\PermissionNameEnum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        // \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\QueryExecuted::class, function ($event) {
        //     if (str($event->sql)->is('*telescope_entries*')) {
        //         return;
        //     }
        //     if (str($event->sql)->is('*telescope_monitoring*')) {
        //         return;
        //     }
        //     // 日誌參數
        //     $logs = logs()->build(['driver' => 'daily', 'path' => storage_path('logs/database/query.log')]);

        //     //
        //     $bindings = collect($event->bindings)->map(fn ($value) => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value)->all();

        //     // SQL 紀錄
        //     $string = str($event->sql)->replaceArray('?', $bindings);

        //     // 寫入日誌
        //     $logs->info($string);
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if (!env('ROLE_PERMISSION_ENABLED')) return true;

            return $user->checkPermissionTo($ability) || $user->checkPermissionTo(PermissionNameEnum::所有權限) || $user->id === 1;
        });
    }
}
