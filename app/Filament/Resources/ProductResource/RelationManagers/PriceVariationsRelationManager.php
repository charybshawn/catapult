<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

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
    
    protected static ?string $title = 'Price Variations';

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
                            ->helperText('Only one variation can be the default.')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive variations won\'t be used for pricing.')
                            ->default(true),
                        Forms\Components\Toggle::make('is_global')
                            ->label('Make Global')
                            ->helperText('When enabled, this price variation template will be available for all products')
                            ->default(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                
                // Create standard variations action
                Tables\Actions\Action::make('create_standard')
                    ->label('Create Standard Variations')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (RelationManager $livewire) {
                        // Create all standard price variations
                        $variations = $livewire->ownerRecord->createAllStandardPriceVariations();
                        $count = count($variations);
                        
                        if ($count > 0) {
                            Notification::make()
                                ->title("Created {$count} standard price variations")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('No new price variations needed')
                                ->body('Standard price variations already exist for this product.')
                                ->info()
                                ->send();
                        }
                    }),
                
                // Add action to apply global price variations to this product
                Tables\Actions\Action::make('apply_global')
                    ->label('Apply Global Variation')
                    ->icon('heroicon-o-globe-alt')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_default')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->hidden(fn ($record) => $record->is_default)
                    ->action(function ($record, RelationManager $livewire) {
                        // Set this as the default and remove default from all others
                        $record->update(['is_default' => true]);
                        
                        $livewire->ownerRecord->priceVariations()
                            ->where('id', '!=', $record->id)
                            ->where('is_default', true)
                            ->update(['is_default' => false]);
                            
                        Notification::make()
                            ->title('Default price variation updated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                            
                            Notification::make()
                                ->title('Price variations activated')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records, RelationManager $livewire) {
                            $defaultIds = $records->where('is_default', true)->pluck('id');
                            
                            // Don't deactivate default variations
                            if ($defaultIds->count() > 0) {
                                Notification::make()
                                    ->title('Cannot deactivate default price variation')
                                    ->body('Please set another variation as default first.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                            
                            Notification::make()
                                ->title('Price variations deactivated')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No price variations')
            ->emptyStateDescription('Create price variations to set different prices based on customer type or unit of measure.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Price Variation'),
                Tables\Actions\Action::make('create_standard')
                    ->label('Create Standard Variations')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (RelationManager $livewire) {
                        // Create all standard price variations
                        $livewire->ownerRecord->createAllStandardPriceVariations();
                        
                        Notification::make()
                            ->title('Standard price variations created')
                            ->success()
                            ->send();
                    }),
            ]);
    }
} 