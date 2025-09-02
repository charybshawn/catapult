<?php

namespace App\Providers\Filament;

use Filament\Support\Enums\Width;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\WeeklyPlanning;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
use App\Filament\Widgets\CropPlanStatusWidget;
use App\Filament\Widgets\SeedPriceTrendsWidget;
use App\Filament\Widgets\SeedReorderAdvisorWidget;
use App\Filament\Widgets\TimeCardSummaryWidget;
use App\Http\Middleware\TimeTrackingMiddleware;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Guava\Calendar\CalendarPlugin;

/**
 * Filament admin panel provider for Catapult agricultural management system.
 * Configures comprehensive farm management interface with specialized navigation,
 * agricultural widgets, calendar integration, and customized branding for microgreens operations.
 *
 * @business_domain Agricultural microgreens farm management and production monitoring
 * @panel_configuration Full-width layout, amber theme, dark mode support
 * @navigation_groups Organized by farm operations: Production, Inventory, Orders, System
 * @widget_integration Crop alerts, planning status, seed trends, time tracking
 * @calendar_plugin Integrated for crop planning and order scheduling workflows
 * @security_middleware Full authentication and session management stack
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Configure the Filament admin panel for agricultural farm management.
     * Sets up comprehensive interface for microgreens production including navigation,
     * widgets, themes, and specialized agricultural functionality.
     *
     * @agricultural_navigation Organized by farm operations and workflow stages
     * @widget_dashboard Crop status, seed management, time tracking, planning tools
     * @theme_configuration Amber primary color, full-width layout, dark mode support
     * @security_stack Complete authentication and middleware protection
     * @calendar_integration Crop planning and order scheduling calendar functionality
     * @return Panel Fully configured Filament panel for agricultural management
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->darkMode()
            ->brandName('Catapult Farm')
            ->maxContentWidth(Width::Full)
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
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                WeeklyPlanning::class,
            ])
            ->homeUrl('/admin')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->spa()
            ->plugins([
                CalendarPlugin::make(),
            ])
            ->widgets([
                // Removed AccountWidget to hide welcome message and sign out button
                CropPlanStatusWidget::class,
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