<?php

namespace App\Filament\Resources\SeedCultivarResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SeedEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'seedEntries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('supplier_product_title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('supplier_product_url')
                    ->required()
                    ->maxLength(255)
                    ->url(),
                Forms\Components\TextInput::make('image_url')
                    ->maxLength(255)
                    ->url()
                    ->nullable(),
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('supplier_product_title')
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier_product_title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('variations_count')
                    ->counts('variations')
                    ->label('Variations'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
} 