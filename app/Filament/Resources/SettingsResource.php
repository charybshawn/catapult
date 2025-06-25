<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingsResource\Pages;
use App\Models\Setting;
use App\Models\Recipe;
use App\Models\Crop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Notifications\Notification;

class SettingsResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Advanced Settings';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false;
    
    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General Settings')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label('Site Name')
                                    ->default(function () {
                                        return Setting::getValue('site_name', 'Catapult Microgreens');
                                    }),
                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary Color')
                                    ->default(function () {
                                        return Setting::getValue('primary_color', '#4f46e5');
                                    }),
                            ]),
                            
                        Tabs\Tab::make('Recipe Changes')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Forms\Components\Section::make('Update Existing Grows with Recipe Changes')
                                    ->description('This tool allows you to update existing grows with changes from their recipes. Use with caution as this will modify existing data.')
                                    ->schema([
                                        Forms\Components\Select::make('recipe_id')
                                            ->label('Recipe')
                                            ->options(Recipe::pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Forms\Set $set) => $set('affected_grows_count', null)),
                                            
                                        Forms\Components\Select::make('current_stage')
                                            ->label('Current Stage Filter')
                                            ->options([
                                                'all' => 'All Stages',
                                                'germination' => 'Germination Only',
                                                'blackout' => 'Blackout Only',
                                                'light' => 'Light Only',
                                            ])
                                            ->default('all')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Forms\Set $set) => $set('affected_grows_count', null)),
                                            
                                        Forms\Components\Placeholder::make('affected_grows_count')
                                            ->label('Affected Grows')
                                            ->content(function (Forms\Get $get, Forms\Set $set) {
                                                $recipeId = $get('recipe_id');
                                                $stage = $get('current_stage');
                                                
                                                if (!$recipeId) {
                                                    return 'Please select a recipe';
                                                }
                                                
                                                $query = Crop::where('recipe_id', $recipeId)
                                                    ->where('current_stage', '!=', 'harvested');
                                                
                                                if ($stage !== 'all') {
                                                    $query->where('current_stage', $stage);
                                                }
                                                
                                                $count = $query->count();
                                                
                                                return "{$count} grows will be affected";
                                            }),
                                            
                                        Forms\Components\Checkbox::make('update_germination_days')
                                            ->label('Update Germination Days'),
                                            
                                        Forms\Components\Checkbox::make('update_blackout_days')
                                            ->label('Update Blackout Days'),
                                            
                                        Forms\Components\Checkbox::make('update_light_days')
                                            ->label('Update Light Days'),
                                            
                                        Forms\Components\Checkbox::make('update_days_to_maturity')
                                            ->label('Update Days to Maturity'),
                                            
                                        Forms\Components\Checkbox::make('update_expected_harvest_dates')
                                            ->label('Update Expected Harvest Dates')
                                            ->helperText('This will recalculate harvest dates based on the recipe settings'),
                                            
                                        Forms\Components\Checkbox::make('confirm_updates')
                                            ->label('I understand this will modify existing grows')
                                            ->required()
                                            ->helperText('This action cannot be undone. Please back up your data before proceeding.'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'float' => 'Float',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
            'recipe-updates' => Pages\RecipeUpdates::route('/recipe-updates'),
        ];
    }
} 