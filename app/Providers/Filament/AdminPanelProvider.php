<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\CropAlertResource;
use App\Filament\Resources\TaskScheduleResource;
use App\Filament\Widgets\SeedPriceTrendsWidget;
use App\Filament\Widgets\SeedReorderAdvisorWidget;

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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                'Dashboard & Overview',
                'Production Management', 
                'Seed Management',
                'Inventory & Materials',
                'Sales & Products',
                'Order Management',
                'Analytics & Reports',
                'System & Settings',
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
            ->homeUrl('/admin/dashboard')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Removed AccountWidget to hide welcome message and sign out button
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
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
