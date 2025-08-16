<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class SystemSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::getValue('site_name', 'Catapult Microgreens'),
            'primary_color' => Setting::getValue('primary_color', '#4f46e5'),
            'auto_backup_before_cascade_delete' => Setting::getValue('auto_backup_before_cascade_delete', true),
            'debug_mode_enabled' => Setting::getValue('debug_mode_enabled', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General Settings')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label('Site Name')
                                    ->required(),
                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary Color')
                                    ->required(),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Database & Backup')
                            ->icon('heroicon-o-circle-stack')
                            ->schema([
                                Forms\Components\Section::make('Automatic Backup Settings')
                                    ->description('Configure when the system should automatically create database backups for data protection')
                                    ->schema([
                                        Forms\Components\Toggle::make('auto_backup_before_cascade_delete')
                                            ->label('Auto Backup Before Cascading Deletes')
                                            ->helperText('Automatically create a database backup before performing operations that cascade delete related data (customers, orders, products, etc.). This provides data protection in case of accidental deletions.')
                                            ->inline(false),
                                    ]),
                                Forms\Components\Section::make('Protected Models Information')
                                    ->description('Information about which models are protected by automatic backups')
                                    ->schema([
                                        Forms\Components\Placeholder::make('critical_models')
                                            ->label('Critical Models (High Risk)')
                                            ->content('Users (Customers), Orders, Products, Suppliers - These have the most extensive cascading relationships'),
                                        Forms\Components\Placeholder::make('important_models')
                                            ->label('Important Models (Medium Risk)')
                                            ->content('Recipes, Time Cards, Master Seed Catalog, Product Mix, Packaging Types'),
                                        Forms\Components\Placeholder::make('backup_location')
                                            ->label('Backup Storage')
                                            ->content('Backup files are stored in storage/app/backups/database/ and can be managed via the Database Management page.')
                                            ->helperText('Backup files are named: cascade_delete_[model]_[id]_[timestamp].sql'),
                                    ]),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Development Settings')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Forms\Components\Section::make('Debug & Development')
                                    ->description('Settings for development and debugging purposes.')
                                    ->schema([
                                        Forms\Components\Toggle::make('debug_mode_enabled')
                                            ->label('Debug Mode')
                                            ->helperText('Enable debug information in admin interface (debug panels, detailed error messages, etc.)')
                                            ->inline(false),
                                        
                                        Forms\Components\Placeholder::make('debug_status')
                                            ->label('Current Status')
                                            ->content(function (Forms\Get $get) {
                                                $enabled = $get('debug_mode_enabled') ?? Setting::getValue('debug_mode_enabled', false);
                                                if ($enabled) {
                                                    return '🟢 Debug mode is ENABLED - Debug panels and detailed information will be visible in crop batch actions';
                                                } else {
                                                    return '🔴 Debug mode is DISABLED - Only essential information will be shown';
                                                }
                                            }),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                // Skip placeholder fields that aren't actual settings
                if (in_array($key, ['critical_models', 'important_models', 'backup_location', 'debug_status'])) {
                    continue;
                }
                
                // Set appropriate group based on setting key
                $group = match($key) {
                    'site_name', 'primary_color' => 'general',
                    'auto_backup_before_cascade_delete' => 'backup',
                    'debug_mode_enabled' => 'development',
                    default => 'general'
                };
                
                Setting::setValue($key, $value, null, $group);
            }

            Notification::make()
                ->title('Settings updated successfully')
                ->success()
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('database_management')
                ->label('Database Management')
                ->icon('heroicon-o-circle-stack')
                ->color('gray')
                ->url('/admin/database-management')
                ->tooltip('Manage database backups and restoration'),
        ];
    }
}