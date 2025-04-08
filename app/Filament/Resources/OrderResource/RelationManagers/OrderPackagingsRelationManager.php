<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderPackagingsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderPackagings';
    
    protected static ?string $title = 'Packaging';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('packaging_type_id')
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
                    
                Forms\Components\TextInput::make('quantity')
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
                    
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('capacity_volume')
                            ->label('Volume Per Unit')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\TextInput::make('volume_unit')
                            ->label('Unit')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                    
                Forms\Components\TextInput::make('cost_per_unit')
                    ->label('Cost Per Unit ($)')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefix('$'),
                    
                Forms\Components\TextInput::make('total_cost')
                    ->label('Total Cost ($)')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefix('$'),
                    
                Forms\Components\TextInput::make('total_volume')
                    ->label('Total Volume')
                    ->disabled()
                    ->dehydrated(false)
                    ->suffix(fn ($get) => $get('volume_unit')),
                    
                Forms\Components\Textarea::make('notes')
                    ->maxLength(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('packagingType.display_name')
                    ->label('Packaging Type')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('volume_info')
                    ->label('Volume')
                    ->getStateUsing(function ($record) {
                        return $record->packagingType->capacity_volume . ' ' . $record->packagingType->volume_unit;
                    }),
                    
                Tables\Columns\TextColumn::make('total_volume')
                    ->label('Total Volume')
                    ->getStateUsing(function ($record) {
                        return number_format($record->quantity * $record->packagingType->capacity_volume, 2) . ' ' . 
                            $record->packagingType->volume_unit;
                    }),
                    
                Tables\Columns\TextColumn::make('packagingType.cost_per_unit')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('getTotalCostAttribute')
                    ->label('Total Cost')
                    ->money('USD')
                    ->getStateUsing(fn ($record) => $record->getTotalCostAttribute()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Remove calculated fields that don't exist in the database
                        unset($data['total_cost'], $data['total_volume']);
                        return $data;
                    }),
                    
                Tables\Actions\Action::make('autoAssignPackaging')
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
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Remove calculated fields that don't exist in the database
                        unset($data['total_cost'], $data['total_volume']);
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
