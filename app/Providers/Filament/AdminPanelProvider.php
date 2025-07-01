<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\CropAlertResource;
use App\Filament\Widgets\SeedPriceTrendsWidget;
use App\Filament\Widgets\SeedReorderAdvisorWidget;
use App\Filament\Widgets\TimeCardSummaryWidget;
use App\Http\Middleware\TimeTrackingMiddleware;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
// use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->darkMode()
            ->brandName('Catapult Farm')
            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                'Dashboard & Overview',
                'Production',
                'Products & Inventory',
                'Orders & Sales',
                'Customers',
                'System',
            ])
            ->resources([
                CropAlertResource::class,
                // Hide the original TaskScheduleResource from navigation but keep it available for now
                // Will be completely removed in a future update
                // This approach allows us to switch to the new resource without breaking existing links
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\WeeklyPlanning::class,
            ])
            ->homeUrl('/admin')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
                // FilamentFullCalendarPlugin::make(),
            ])
            ->widgets([
                // Removed AccountWidget to hide welcome message and sign out button
                TimeCardSummaryWidget::class,
                SeedPriceTrendsWidget::class,
                SeedReorderAdvisorWidget::class,
            ])
            ->userMenuItems([])
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
                TimeTrackingMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => Blade::render('@livewire(\'time-clock-widget\')')
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn () => view('components.dashboard-quick-link')->render()
            );
    }
}
