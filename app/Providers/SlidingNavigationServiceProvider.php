<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class SlidingNavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Inject our custom sliding navigation and hide the default one
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn (): string => view('filament.navigation.sliding-navigation')->render() . 
                            '<style>.fi-sidebar-nav-groups { display: none !important; }</style>'
        );
    }
}