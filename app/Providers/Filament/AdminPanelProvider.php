<?php

namespace App\Providers\Filament;

use App\Enums\PermissionNameEnum;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // ->login()
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->navigationItems([
                NavigationItem::make()
                    ->label('新版後台')
                    ->icon('heroicon-s-globe-alt')
                    ->url(fn () => route('dashboard')),
                NavigationItem::make()
                    ->visible(fn (): bool => Gate::check(PermissionNameEnum::所有訂單))
                    ->label('所有訂單')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->group('訂單管理')
                    ->sort(100)
                    ->url(fn () => route('orders.index')),
                NavigationItem::make()
                    ->visible(fn (): bool => Gate::check(PermissionNameEnum::所有商品))
                    ->label('所有商品')
                    ->icon('heroicon-o-rectangle-stack')
                    ->group('商品管理')
                    ->sort(100)
                    ->url(fn () => route('products.index')),
                NavigationItem::make()
                    ->visible(fn (): bool => Gate::check(PermissionNameEnum::庫存管理))
                    ->label('庫存管理')
                    ->icon('heroicon-o-archive-box')
                    ->group('商品管理')
                    ->sort(300)
                    ->url(fn () => route('inventories.index')),
                NavigationItem::make()
                    ->visible(fn (): bool => Gate::check(PermissionNameEnum::所有客戶))
                    ->label('所有客戶')
                    ->icon('heroicon-o-archive-box')
                    ->group('客戶管理')
                    ->sort(100)
                    ->url(fn () => route('customers.index')),
                NavigationItem::make()
                    ->visible(fn (): bool => Gate::check(PermissionNameEnum::銷售報告))
                    ->label('銷售報告')
                    ->icon('heroicon-o-chart-bar')
                    ->group('分析報表')
                    ->sort(100)
                    ->url(fn () => route('analytics.index')),
                NavigationItem::make()
                    ->visible(fn (): bool => Gate::check(PermissionNameEnum::地址設定))
                    ->label('地址設定')
                    ->icon('heroicon-o-map-pin')
                    ->group('設定')
                    ->sort(600)
                    ->url(fn () => route('locations.index')),
            ])
            ->navigationGroups([
                '訂單管理',
                '商品管理',
                '客戶管理',
                '分析報表',
                '設定',
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
