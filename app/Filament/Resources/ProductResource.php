<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Component;
use App\Http\Livewire\ProductPriceCalculator;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;

class ProductResource extends BaseResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Products';
    protected static ?string $navigationGroup = 'Sales & Products';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make(static::getFormSchema($form->getLivewire()))
                    ->skippable()
                    ->persistStepInQueryString('product-step')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('default_photo')
                    ->label('Image')
                    ->circular()
                    ->lazy(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_product_mix')
                    ->label('Mix')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->productMix !== null)
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible_in_store')
                    ->label('In Store')
                    ->boolean()
                    ->sortable(),
                ...static::getTimestampColumns(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('has_product_mix')
                    ->label('Has Mix')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('productMix'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('productMix'),
                    ),
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\TernaryFilter::make('is_visible_in_store')
                    ->label('Visible in Store'),
            ])
            ->actions(static::getDefaultTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ...static::getDefaultBulkActions(),
                    Tables\Actions\BulkAction::make('show_in_store')
                        ->label('Show in Store')
                        ->icon('heroicon-o-eye')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_visible_in_store' => true]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('hide_from_store')
                        ->label('Hide from Store')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_visible_in_store' => false]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceVariationsRelationManager::class,
        ];
    }

    /**
     * Get the panels that should be displayed for viewing a record.
     */
    public static function getPanels(): array
    {
        try {
            \Illuminate\Support\Facades\Log::info('ProductResource: getPanels method called');
            
            return [
                'price_variations' => Forms\Components\Section::make('Price Variations')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('base_price_display')
                                    ->label('Default Price')
                                    ->content(function ($record) {
                                        $variation = $record->defaultPriceVariation();
                                        return $variation 
                                            ? '$' . number_format($variation->price, 2) . ' (' . $variation->name . ')'
                                            : '$' . number_format($record->base_price ?? 0, 2);
                                    }),
                                Forms\Components\Placeholder::make('variations_count')
                                    ->label('Price Variations')
                                    ->content(function ($record) {
                                        $count = $record->priceVariations()->count();
                                        $activeCount = $record->priceVariations()->where('is_active', true)->count();
                                        return "{$activeCount} active / {$count} total";
                                    }),
                            ]),
                        Forms\Components\Placeholder::make('variations_info')
                            ->content(function ($record) {
                                $priceTypes = ['Default', 'Wholesale', 'Bulk', 'Special'];
                                $content = "Price variations allow you to set different prices based on customer type or purchase unit.";
                                
                                $missingTypes = [];
                                foreach ($priceTypes as $type) {
                                    if (!$record->priceVariations()->where('name', $type)->exists()) {
                                        $missingTypes[] = $type;
                                    }
                                }
                                
                                if (!empty($missingTypes)) {
                                    $content .= "<br><br>Standard pricing types not yet created: <span class='text-primary-500'>" . implode(', ', $missingTypes) . "</span>";
                                }
                                
                                return $content;
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'prose']),
                        Forms\Components\ViewField::make('price_variations_panel')
                            ->view('filament.resources.product-resource.partials.price-variations')
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
                
                'product_mix' => Forms\Components\Section::make('Product Mix')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('mix_name')
                                    ->label('Mix Name')
                                    ->content(function ($record) {
                                        return $record->productMix ? $record->productMix->name : 'No mix assigned';
                                    }),
                                Forms\Components\Placeholder::make('variety_count')
                                    ->label('Number of Varieties')
                                    ->content(function ($record) {
                                        return $record->productMix ? $record->productMix->seedVarieties->count() : '0';
                                    }),
                            ]),
                        Forms\Components\Placeholder::make('varieties')
                            ->label('Varieties in Mix')
                            ->content(function ($record) {
                                if (!$record->productMix) {
                                    return 'No mix assigned';
                                }
                                
                                $varieties = $record->productMix->seedVarieties;
                                if ($varieties->isEmpty()) {
                                    return 'No varieties in this mix';
                                }
                                
                                $content = '<ul class="list-disc list-inside space-y-1">';
                                foreach ($varieties as $variety) {
                                    $percentage = $variety->pivot->percentage ?? 0;
                                    $content .= "<li><strong>{$variety->name}</strong> ({$percentage}%)</li>";
                                }
                                $content .= '</ul>';
                                
                                return $content;
                            })
                            ->extraAttributes(['class' => 'prose'])
                            ->columnSpanFull(),
                    ])
                    ->hidden(function ($record) {
                        return $record->productMix === null;
                    })
                    ->collapsible()
                    ->columnSpanFull(),
            ];
        } catch (\Throwable $e) {
            \App\Services\DebugService::logError($e, 'ProductResource::getPanels');
            
            // Return a minimal panel set that won't cause errors
            return [
                'debug' => Forms\Components\Section::make('Debug Information')
                    ->schema([
                        Forms\Components\Placeholder::make('error')
                            ->label('Error')
                            ->content('An error occurred loading panels: ' . $e->getMessage()),
                    ]),
            ];
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
    
    /**
     * Get the form schema for the wizard steps
     */
    public static function getFormSchema($livewire): array
    {
        return [
            Step::make('Basic Information')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->maxLength(65535),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                            return $action
                                ->modalHeading('Create category')
                                ->modalSubmitActionLabel('Create category')
                                ->modalWidth('lg');
                        }),
                    Forms\Components\Select::make('product_mix_id')
                        ->label('Product Mix')
                        ->relationship('productMix', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('If this product uses a mix of varieties, select the mix here.'),
                    Toggle::make('active')
                        ->label('Active')
                        ->default(true),
                    Toggle::make('is_visible_in_store')
                        ->label('Visible in Store')
                        ->default(true)
                        ->helperText('Whether this product is visible to customers in the online store'),
                ])
                ->columns(3),
            Step::make('Pricing')
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('base_price')
                                ->label('Base Price')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('Standard price for retail customers.'),
                            Forms\Components\TextInput::make('wholesale_price')
                                ->label('Wholesale Price')
                                ->numeric()
                                ->prefix('$')
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('Discounted price for wholesale customers.'),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('bulk_price')
                                ->label('Bulk Price')
                                ->numeric()
                                ->prefix('$')
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('Discounted price for bulk purchases.'),
                            Forms\Components\TextInput::make('special_price')
                                ->label('Special Price')
                                ->numeric()
                                ->prefix('$')
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('Special promotional price.'),
                        ]),
                    Forms\Components\Placeholder::make('price_variations_info')
                        ->content('Price variations will be automatically created based on the prices entered above. After saving, you can add additional variations or modify existing ones.')
                        ->columnSpanFull(),
                    Forms\Components\ViewField::make('price_calculator')
                        ->view('livewire.product-price-calculator')
                        ->visible(function ($livewire) {
                            return $livewire->record !== null;
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Step::make('Product Photos')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('new_photos')
                        ->label('Photos')
                        ->multiple()
                        ->image()
                        ->directory('product-photos')
                        ->maxSize(5120)
                        ->imageResizeTargetWidth('1200')
                        ->imageResizeTargetHeight('1200')
                        ->disk('public')
                        ->helperText('Upload one or more photos. The first photo will be set as default.')
                        ->afterStateUpdated(function ($state, $livewire) {
                            // Skip if no photos were uploaded
                            if (empty($state)) {
                                return;
                            }
                            
                            $product = $livewire->record;
                            if (!$product) {
                                // Save the uploaded photos to be used after record creation
                                $livewire->temporaryPhotos = $state;
                                return;
                            }
                            
                            // Get next order value
                            $maxOrder = $product->photos()->max('order');
                            $maxOrder = is_numeric($maxOrder) ? (int)$maxOrder : 0;
                            
                            // Check if we have any default photos
                            $hasDefault = $product->photos()->where('is_default', true)->exists();
                            
                            // Process each uploaded photo
                            foreach ($state as $index => $path) {
                                // Set the first one as default if no default exists
                                $isDefault = ($index === 0 && !$hasDefault);
                                
                                $photo = $product->photos()->create([
                                    'photo' => $path,
                                    'is_default' => $isDefault,
                                    'order' => $maxOrder + $index + 1,
                                ]);
                                
                                // If this is the default, ensure it's properly set
                                if ($isDefault) {
                                    $photo->setAsDefault();
                                }
                            }
                            
                            // Clear the upload field
                            $livewire->form->fill([
                                'new_photos' => null,
                            ]);
                            
                            // Refresh the page to show the new photos if we're on the edit page
                            if ($livewire->record) {
                                $livewire->redirect(ProductResource::getUrl('edit', ['record' => $livewire->record]));
                            }
                        }),
                ]),
        ];
    }
} 