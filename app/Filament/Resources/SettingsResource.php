<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\CropStage;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SettingsResource\Pages\ListSettings;
use App\Filament\Resources\SettingsResource\Pages\CreateSetting;
use App\Filament\Resources\SettingsResource\Pages\EditSetting;
use App\Filament\Resources\SettingsResource\Pages\RecipeUpdates;
use App\Filament\Resources\SettingsResource\Pages;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\Setting;
use Filament\Forms;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingsResource extends BaseResource
{
    protected static ?string $model = Setting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Advanced Settings';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('General Settings')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('Site Name')
                                    ->default(function () {
                                        return Setting::getValue('site_name', 'Catapult Microgreens');
                                    }),
                                ColorPicker::make('primary_color')
                                    ->label('Primary Color')
                                    ->default(function () {
                                        return Setting::getValue('primary_color', '#4f46e5');
                                    }),
                            ]),

                        Tab::make('Recipe Changes')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Section::make('Update Existing Grows with Recipe Changes')
                                    ->description('This tool allows you to update existing grows with changes from their recipes. Use with caution as this will modify existing data.')
                                    ->schema([
                                        Select::make('recipe_id')
                                            ->label('Recipe')
                                            ->options(Recipe::pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set) => $set('affected_grows_count', null)),

                                        Select::make('current_stage')
                                            ->label('Current Stage Filter')
                                            ->options([
                                                'all' => 'All Stages',
                                                'germination' => 'Germination Only',
                                                'blackout' => 'Blackout Only',
                                                'light' => 'Light Only',
                                            ])
                                            ->default('all')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set) => $set('affected_grows_count', null)),

                                        Placeholder::make('affected_grows_count')
                                            ->label('Affected Grows')
                                            ->content(function (Get $get, Set $set) {
                                                $recipeId = $get('recipe_id');
                                                $stage = $get('current_stage');

                                                if (! $recipeId) {
                                                    return 'Please select a recipe';
                                                }

                                                $harvestedStage = CropStage::findByCode('harvested');
                                                $query = Crop::where('recipe_id', $recipeId)
                                                    ->where('current_stage_id', '!=', $harvestedStage?->id);

                                                if ($stage !== 'all') {
                                                    $stageRecord = CropStage::findByCode($stage);
                                                    if ($stageRecord) {
                                                        $query->where('current_stage_id', $stageRecord->id);
                                                    }
                                                }

                                                $count = $query->count();

                                                return "{$count} grows will be affected";
                                            }),

                                        Checkbox::make('update_germination_days')
                                            ->label('Update Germination Days'),

                                        Checkbox::make('update_blackout_days')
                                            ->label('Update Blackout Days'),

                                        Checkbox::make('update_light_days')
                                            ->label('Update Light Days'),

                                        Checkbox::make('update_days_to_maturity')
                                            ->label('Update Days to Maturity'),

                                        Checkbox::make('update_expected_harvest_dates')
                                            ->label('Update Expected Harvest Dates')
                                            ->helperText('This will recalculate harvest dates based on the recipe settings'),

                                        Checkbox::make('confirm_updates')
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
            ->persistSearchInSession()->columns([
                TextColumn::make('key')
                    ->searchable(),
                TextColumn::make('value')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'float' => 'Float',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
            'recipe-updates' => RecipeUpdates::route('/recipe-updates'),
        ];
    }
}
