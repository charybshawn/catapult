<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HarvestResource\Pages;
use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class HarvestResource extends BaseResource
{
    protected static ?string $model = Harvest::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Harvests';

    protected static ?string $navigationGroup = 'Production';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationParentItem = 'Grows';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Harvest Details')
                    ->schema([
                        Forms\Components\Select::make('master_cultivar_id')
                            ->label('Crop Variety')
                            ->options(function () {
                                return MasterCultivar::with('masterSeedCatalog')
                                    ->where('is_active', true)
                                    ->whereHas('masterSeedCatalog', function ($query) {
                                        $query->where('is_active', true);
                                    })
                                    ->get()
                                    ->mapWithKeys(function ($cultivar) {
                                        return [$cultivar->id => $cultivar->full_name];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->reactive(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_weight_grams')
                                    ->label('Total Weight (grams)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01),
                                Forms\Components\TextInput::make('tray_count')
                                    ->label('Number of Trays')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->integer(),
                            ]),
                        Forms\Components\DatePicker::make('harvest_date')
                            ->label('Harvest Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('harvest_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('masterCultivar.full_name')
                    ->label('Variety')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('masterCultivar', function (Builder $query) use ($search) {
                            $query->where('cultivar_name', 'like', "%{$search}%")
                                ->orWhereHas('masterSeedCatalog', function (Builder $query) use ($search) {
                                    $query->where('common_name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Check if joins already exist to avoid duplicates
                        $joins = collect($query->getQuery()->joins);
                        
                        if (!$joins->pluck('table')->contains('master_cultivars')) {
                            $query->join('master_cultivars', 'harvests.master_cultivar_id', '=', 'master_cultivars.id');
                        }
                        
                        if (!$joins->pluck('table')->contains('master_seed_catalog')) {
                            $query->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id');
                        }
                        
                        return $query
                            ->orderBy('master_seed_catalog.common_name', $direction)
                            ->orderBy('master_cultivars.cultivar_name', $direction);
                    }),
                Tables\Columns\TextColumn::make('total_weight_grams')
                    ->label('Total Weight')
                    ->suffix(' g')
                    ->numeric(1)
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->numeric(1)
                            ->suffix(' g'),
                    ]),
                Tables\Columns\TextColumn::make('tray_count')
                    ->label('Trays')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total'),
                    ]),
                Tables\Columns\TextColumn::make('average_weight_per_tray')
                    ->label('Avg/Tray')
                    ->suffix(' g')
                    ->numeric(1)
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()
                            ->label('Average')
                            ->numeric(1)
                            ->suffix(' g'),
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Harvested By')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('harvest_date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->selectRaw("
                    harvests.*,
                    CONCAT(harvests.master_cultivar_id, '_', DATE_FORMAT(harvests.harvest_date, '%Y-%m-%d')) as variety_date_key
                ");
            })
            ->groups([
                Tables\Grouping\Group::make('variety_date_key')
                    ->label('Variety & Date')
                    ->getTitleFromRecordUsing(function (Harvest $record): string {
                        return $record->masterCultivar->full_name . ' - ' . $record->harvest_date->format('M j, Y');
                    })
                    ->orderQueryUsing(function (Builder $query, string $direction) {
                        // Check if joins already exist to avoid duplicates
                        $joins = collect($query->getQuery()->joins);
                        
                        if (!$joins->pluck('table')->contains('master_cultivars')) {
                            $query->join('master_cultivars', 'harvests.master_cultivar_id', '=', 'master_cultivars.id');
                        }
                        
                        if (!$joins->pluck('table')->contains('master_seed_catalog')) {
                            $query->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id');
                        }
                        
                        return $query
                            ->orderBy('harvests.harvest_date', $direction)
                            ->orderBy('master_seed_catalog.common_name', $direction)
                            ->orderBy('master_cultivars.cultivar_name', $direction);
                    })
                    ->collapsible(),
                Tables\Grouping\Group::make('harvest_date')
                    ->label('Date Only')
                    ->date()
                    ->collapsible(),
                Tables\Grouping\Group::make('master_cultivar_id')
                    ->label('Variety Only')
                    ->getTitleFromRecordUsing(fn (Harvest $record): string => $record->masterCultivar->full_name)
                    ->orderQueryUsing(function (Builder $query, string $direction) {
                        // Check if joins already exist to avoid duplicates
                        $joins = collect($query->getQuery()->joins);
                        
                        if (!$joins->pluck('table')->contains('master_cultivars')) {
                            $query->join('master_cultivars', 'harvests.master_cultivar_id', '=', 'master_cultivars.id');
                        }
                        
                        if (!$joins->pluck('table')->contains('master_seed_catalog')) {
                            $query->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id');
                        }
                        
                        return $query
                            ->orderBy('master_seed_catalog.common_name', $direction)
                            ->orderBy('master_cultivars.cultivar_name', $direction);
                    })
                    ->collapsible(),
            ])
            ->defaultGroup('variety_date_key')
            ->groupsInDropdownOnDesktop()
            ->filters([
                Tables\Filters\SelectFilter::make('master_cultivar_id')
                    ->label('Variety')
                    ->options(function () {
                        return MasterCultivar::with('masterSeedCatalog')
                            ->whereHas('harvests')
                            ->get()
                            ->mapWithKeys(function ($cultivar) {
                                return [$cultivar->id => $cultivar->full_name];
                            });
                    })
                    ->searchable(),
                Tables\Filters\Filter::make('harvest_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('harvest_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('harvest_date', '<=', $date),
                            );
                    }),
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
            'index' => Pages\ListHarvests::route('/'),
            'create' => Pages\CreateHarvest::route('/create'),
            'edit' => Pages\EditHarvest::route('/{record}/edit'),
        ];
    }
}
