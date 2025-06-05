<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GroupedCropAlertsWidget;
use Filament\Pages\Page;

class CropDashboard extends Page
{
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'Alerts Dashboard';
    protected static ?string $navigationGroup = 'Dashboard & Overview';
    
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Crop Alerts Dashboard';
    
    protected static string $view = 'filament.pages.crop-dashboard';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            GroupedCropAlertsWidget::class,
        ];
    }
} 