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
                        Forms\Components\Select::make('packaging_type_id')
                            ->relationship('packagingType', 'name')
                            ->label('Packaging Type')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
                    
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('fill_weight_grams')
                            ->label('Fill Weight (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->helperText('The actual weight of product that goes into the packaging')
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
                Tables\Columns\TextColumn::make('packagingType.name')
                    ->label('Packaging Type')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU/UPC')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fill_weight_grams')
                    ->label('Fill Weight')
                    ->formatStateUsing(fn ($state) => $state ? $state . 'g' : 'N/A')
                    ->sortable()
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('packagingType')
                    ->relationship('packagingType', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Packaging Type'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Price'),
                Tables\Filters\TernaryFilter::make('is_global')
                    ->label('Global Pricing'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Set the product_id to the owner record
                        $data['product_id'] = $this->ownerRecord->id;
                        
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_default')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->hidden(fn ($record) => $record->is_default)
                    ->requiresConfirmation()
                    ->modalHeading('Set as Default Price Variation')
                    ->modalDescription('This will make this variation the default price and remove the default status from any other variations.')
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
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Price Variation')
                    ->modalDescription('Are you sure you want to delete this price variation? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Price Variations')
                        ->modalDescription('Are you sure you want to delete the selected price variations? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Price Variations')
                        ->modalDescription('Are you sure you want to activate the selected price variations?')
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
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Price Variations')
                        ->modalDescription('Are you sure you want to deactivate the selected price variations? Note: Default variations cannot be deactivated.')
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
            ->emptyStateDescription('Create price variations to set different prices based on packaging, customer type, or quantity.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create First Price Variation'),
            ]);
    }
} 