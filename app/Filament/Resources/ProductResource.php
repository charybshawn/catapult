<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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

class ProductResource extends Resource
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
                    ->circular(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible_in_store')
                    ->label('In Store')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\TernaryFilter::make('is_visible_in_store')
                    ->label('Visible in Store'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View product details'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit product'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete product'),
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
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        }),
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
        return [
            'price_variations' => Forms\Components\Section::make('Price Variations')
                ->schema([
                    Forms\Components\Placeholder::make('base_price_display')
                        ->label('Base Price')
                        ->content(fn ($record) => '$' . number_format($record->base_price, 2)),
                    Forms\Components\Placeholder::make('variations_info')
                        ->content(function ($record) {
                            $count = $record->priceVariations()->count();
                            return "This product has $count price variation" . ($count !== 1 ? 's' : '');
                        }),
                    Forms\Components\ViewField::make('price_variations_panel')
                        ->view('filament.resources.product-resource.partials.price-variations')
                ])
                ->collapsible()
                ->columnSpanFull(),
        ];
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
                    Forms\Components\TextInput::make('base_price')
                        ->label('Base Price')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->minValue(0)
                        ->step(0.01)
                        ->helperText('This will be used to create a default price variation'),
                    Forms\Components\Placeholder::make('price_variations_info')
                        ->content('You can add additional price variations (wholesale, bulk, etc.) after saving the product.')
                        ->columnSpanFull(),
                    Forms\Components\ViewField::make('price_calculator')
                        ->view('livewire.product-price-calculator')
                        ->visible(function ($livewire) {
                            return $livewire->record !== null;
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),
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