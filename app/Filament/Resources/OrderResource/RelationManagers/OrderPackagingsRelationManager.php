<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderPackagingsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderPackagings';
    
    protected static ?string $title = 'Packaging';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('packaging_type_id')
                    ->label('Packaging Type')
                    ->options(PackagingType::where('is_active', true)->pluck('display_name', 'id'))
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) return;
                        
                        $packagingType = PackagingType::find($state);
                        
                        if ($packagingType) {
                            $set('capacity_volume', $packagingType->capacity_volume);
                            $set('volume_unit', $packagingType->volume_unit);
                            $set('cost_per_unit', $packagingType->cost_per_unit);
                        }
                    }),
                    
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->reactive()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        $quantity = (int) $state;
                        $costPerUnit = (float) $get('cost_per_unit');
                        $capacityVolume = (float) $get('capacity_volume');
                        
                        $set('total_cost', number_format($quantity * $costPerUnit, 2));
                        $set('total_volume', number_format($quantity * $capacityVolume, 2));
                    }),
                    
                Grid::make()
                    ->schema([
                        TextInput::make('capacity_volume')
                            ->label('Volume Per Unit')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('volume_unit')
                            ->label('Unit')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                    
                TextInput::make('cost_per_unit')
                    ->label('Cost Per Unit ($)')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefix('$'),
                    
                TextInput::make('total_cost')
                    ->label('Total Cost ($)')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefix('$'),
                    
                TextInput::make('total_volume')
                    ->label('Total Volume')
                    ->disabled()
                    ->dehydrated(false)
                    ->suffix(fn ($get) => $get('volume_unit')),
                    
                Textarea::make('notes')
                    ->maxLength(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('packagingType.display_name')
                    ->label('Packaging Type')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable(),
                    
                TextColumn::make('volume_info')
                    ->label('Volume')
                    ->getStateUsing(function ($record) {
                        return $record->packagingType->capacity_volume . ' ' . $record->packagingType->volume_unit;
                    }),
                    
                TextColumn::make('total_volume')
                    ->label('Total Volume')
                    ->getStateUsing(function ($record) {
                        return number_format($record->quantity * $record->packagingType->capacity_volume, 2) . ' ' . 
                            $record->packagingType->volume_unit;
                    }),
                    
                TextColumn::make('packagingType.cost_per_unit')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('getTotalCostAttribute')
                    ->label('Total Cost')
                    ->money('USD')
                    ->getStateUsing(fn ($record) => $record->getTotalCostAttribute()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        // Remove calculated fields that don't exist in the database
                        unset($data['total_cost'], $data['total_volume']);
                        return $data;
                    }),
                    
                Action::make('autoAssignPackaging')
                    ->label('Auto Assign Packaging')
                    ->icon('heroicon-o-cube')
                    ->color('primary')
                    ->action(function () {
                        $this->getOwnerRecord()->autoAssignPackaging();
                        $this->refreshList();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Auto Assign Packaging')
                    ->modalDescription('This will assign packaging based on the order items. Any existing packaging assignments will be removed.')
                    ->modalSubmitActionLabel('Yes, assign packaging'),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        // Remove calculated fields that don't exist in the database
                        unset($data['total_cost'], $data['total_volume']);
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
