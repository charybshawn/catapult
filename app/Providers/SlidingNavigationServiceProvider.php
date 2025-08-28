<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use App\Filament\Support\SlidingNavigationBuilder;

/**
 * Sliding Navigation Service Provider for Agricultural Dashboard UI
 * 
 * Provides enhanced navigation experience for agricultural production management
 * by implementing a custom sliding navigation system that replaces Filament's
 * default navigation. This improves usability for complex agricultural workflows
 * requiring quick access to multiple production management tools.
 * 
 * Navigation Features:
 * - Sliding menu system: Space-efficient navigation for agricultural dashboards
 * - Custom navigation builder: Agricultural workflow-optimized menu structure
 * - Default navigation override: Replaces Filament's standard navigation
 * - Submenu support: Hierarchical organization of agricultural management tools
 * 
 * Agricultural Context:
 * - Production workflows require quick access to crop planning, orders, and inventory
 * - Dashboard space is precious for displaying agricultural data and metrics
 * - Complex agricultural operations need intuitive navigation between related functions
 * - Users need efficient access to both operational and planning tools
 * 
 * UI Integration:
 * - Filament render hook integration for seamless dashboard integration
 * - CSS override to hide default navigation without breaking functionality
 * - Custom blade view for agricultural workflow-optimized navigation layout
 * - SlidingNavigationBuilder provides agricultural business logic for menu structure
 * 
 * @business_domain Agricultural dashboard user experience and navigation
 * @ui_enhancement Space-efficient navigation for complex agricultural workflows
 * @filament_integration Custom navigation system integrated with Filament panels
 * 
 * @see \App\Filament\Support\SlidingNavigationBuilder For agricultural menu structure
 * @see resources/views/filament/navigation/sliding-navigation.blade.php For UI template
 */
class SlidingNavigationServiceProvider extends ServiceProvider
{
    /**
     * Register sliding navigation services
     * 
     * No services are registered in this provider as navigation
     * functionality is configured through Filament render hooks
     * in the boot method.
     * 
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap sliding navigation system for agricultural dashboard
     * 
     * Configures custom sliding navigation by injecting it into Filament's
     * sidebar navigation area and hiding the default navigation. This provides
     * a more space-efficient and workflow-optimized navigation experience
     * for agricultural production management.
     * 
     * Implementation Details:
     * - Uses Filament render hooks for seamless integration
     * - Injects custom navigation at SIDEBAR_NAV_START position
     * - Applies CSS override to hide default Filament navigation
     * - Renders custom blade view with agricultural menu structure
     * - Includes submenu support for hierarchical navigation
     * 
     * @return void
     * @business_context Optimizes agricultural dashboard navigation for production workflows
     * @ui_enhancement Provides space-efficient navigation for complex agricultural operations
     */
    public function boot(): void
    {
        // Inject our custom sliding navigation and hide the default one
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            function (): string {
                $navigation = SlidingNavigationBuilder::build();
                return view('filament.navigation.sliding-navigation', [
                    'navigation' => $navigation,
                    'submenus' => $navigation['submenus'] ?? []
                ])->render() . 
                '<style>.fi-sidebar-nav-groups { display: none !important; }</style>';
            }
        );
    }
}