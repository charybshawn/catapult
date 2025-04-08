<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class PriceVariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'priceVariations';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('unit')
                            ->options([
                                'item' => 'Per Item',
                                'lbs' => 'Pounds',
                                'gram' => 'Grams',
                                'kg' => 'Kilograms',
                                'oz' => 'Ounces',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'item') {
                                    $set('weight', null);
                                }
                            })
                            ->required(),
                    ]),
                    
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('weight')
                            ->label('Weight')
                            ->suffix(function (Forms\Get $get): string {
                                $unit = $get('unit');
                                return ($unit && $unit !== 'item') ? $unit : '';
                            })
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Forms\Get $get): bool => $get('unit') && $get('unit') !== 'item')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->columnSpan(1),
                    ]),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Price')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_global')
                            ->label('Make Global')
                            ->helperText('When enabled, this price variation will be available for all products')
                            ->default(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU/UPC')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('weight')
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        if ($record->unit === 'item' || !$record->weight) {
                            return '-';
                        }
                        
                        // Format with appropriate unit
                        return $record->weight . ' ' . $record->unit;
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit')
                    ->options([
                        'item' => 'Per Item',
                        'lbs' => 'Pounds',
                        'gram' => 'Grams',
                        'kg' => 'Kilograms',
                        'oz' => 'Ounces',
                    ]),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Price'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\ViewRecord)
                    ->mutateFormDataUsing(function (array $data) {
                        // Set the item_id to the owner record
                        $data['item_id'] = $this->ownerRecord->id;
                        
                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        // Ensure only one default price variation exists
                        $defaultCount = $livewire->ownerRecord->priceVariations()->where('is_default', true)->count();
                        
                        if ($defaultCount > 1) {
                            // Keep only the most recently created one as default
                            $mostRecentDefault = $livewire->ownerRecord->priceVariations()
                                ->where('is_default', true)
                                ->latest()
                                ->first();
                                
                            $livewire->ownerRecord->priceVariations()
                                ->where('is_default', true)
                                ->where('id', '!=', $mostRecentDefault->id)
                                ->update(['is_default' => false]);
                        }
                    }),
                
                // Add action to apply global price variations to this product
                Tables\Actions\Action::make('apply_global')
                    ->label('Apply Global Variation')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\ViewRecord)
                    ->form([
                        Forms\Components\Select::make('global_variation_id')
                            ->label('Global Price Variation')
                            ->options(function () {
                                return \App\Models\PriceVariation::where('is_global', true)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                        Forms\Components\Toggle::make('set_as_default')
                            ->label('Set as Default Price')
                            ->default(false),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $globalVariation = \App\Models\PriceVariation::findOrFail($data['global_variation_id']);
                        
                        // Create a new price variation based on the global one
                        $newVariation = $livewire->ownerRecord->priceVariations()->create([
                            'name' => $globalVariation->name,
                            'unit' => $globalVariation->unit,
                            'sku' => $globalVariation->sku,
                            'weight' => $globalVariation->weight,
                            'price' => $globalVariation->price,
                            'is_default' => $data['set_as_default'],
                            'is_global' => false,
                            'is_active' => true,
                        ]);
                        
                        // If setting as default, make sure no other variations are default
                        if ($data['set_as_default']) {
                            $livewire->ownerRecord->priceVariations()
                                ->where('id', '!=', $newVariation->id)
                                ->where('is_default', true)
                                ->update(['is_default' => false]);
                        }
                        
                        Notification::make()
                            ->title('Global price variation applied')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\ViewRecord),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\ViewRecord),
                Tables\Actions\Action::make('set_default')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn ($record, $livewire) => !$record->is_default && !$record->is_global && !($livewire instanceof \Filament\Resources\Pages\ViewRecord))
                    ->action(function ($record, RelationManager $livewire) {
                        // Set this variation as the default
                        $record->update(['is_default' => true]);
                        
                        // Remove default status from all other variations for this item
                        $livewire->ownerRecord->priceVariations()
                            ->where('id', '!=', $record->id)
                            ->where('is_default', true)
                            ->update(['is_default' => false]);
                            
                        Notification::make()
                            ->title('Default price variation updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\ViewRecord),
                ]),
            ]);
    }
} 