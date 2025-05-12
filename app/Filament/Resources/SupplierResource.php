<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?string $navigationGroup = 'Inventory & Supplies';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Supplier Name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('type')
                            ->label('Supplier Type')
                            ->options([
                                'seed' => 'Seed Supplier',
                                'soil' => 'Soil Supplier',
                                'packaging' => 'Packaging Supplier',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('other'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Contact Phone')
                            ->tel()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->rows(3),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'seed' => 'success',
                        'soil' => 'warning',
                        'packaging' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'seed' => 'Seed Supplier',
                        'soil' => 'Soil Supplier',
                        'packaging' => 'Packaging Supplier',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive')
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit supplier'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete supplier'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
} 