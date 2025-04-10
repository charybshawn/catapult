<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GroupedCropAlertsWidget;
use Filament\Pages\Page;

class CropDashboard extends Page
{
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'Crop Alerts';
    protected static ?string $navigationGroup = 'Dashboards';
    protected static ?string $title = 'Crop Alerts Dashboard';
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.pages.crop-dashboard';
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            GroupedCropAlertsWidget::class,
        ];
    }
} 