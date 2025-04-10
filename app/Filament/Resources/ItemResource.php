<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
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

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'view' => Pages\ViewItem::route('/{record}'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
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
            Step::make('Product Photos')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('new_photos')
                        ->label('Photos')
                        ->multiple()
                        ->image()
                        ->directory('item-photos')
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
                            
                            $item = $livewire->record;
                            if (!$item) {
                                // Save the uploaded photos to be used after record creation
                                $livewire->temporaryPhotos = $state;
                                return;
                            }
                            
                            // Get next order value
                            $maxOrder = $item->photos()->max('order');
                            $maxOrder = is_numeric($maxOrder) ? (int)$maxOrder : 0;
                            
                            // Check if we have any default photos
                            $hasDefault = $item->photos()->where('is_default', true)->exists();
                            
                            // Process each uploaded photo
                            foreach ($state as $index => $path) {
                                // Set the first one as default if no default exists
                                $isDefault = ($index === 0 && !$hasDefault);
                                
                                $photo = $item->photos()->create([
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
                                $livewire->redirect($livewire->getResource()::getUrl('edit', ['record' => $item]));
                            }
                        })
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    
                    Forms\Components\View::make('filament.components.photo-gallery')
                        ->visible(function ($livewire) {
                            // Only show in edit/view contexts, not in list/index
                            return $livewire instanceof \App\Filament\Resources\ItemResource\Pages\EditItem || 
                                   $livewire instanceof \App\Filament\Resources\ItemResource\Pages\ViewItem;
                        })
                        ->columnSpanFull(),
                ]),
        ];
    }
} 