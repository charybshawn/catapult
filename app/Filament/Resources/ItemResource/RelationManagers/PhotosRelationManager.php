<?php

namespace App\Filament\Resources\ItemResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('photo')
                    ->required()
                    ->image()
                    ->directory('item-photos')
                    ->maxSize(5120)
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('1200'),
                Forms\Components\Toggle::make('is_default')
                    ->label('Default Photo')
                    ->helperText('This will be shown as the main product image')
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                        if (!$state) {
                            return;
                        }
                        
                        // Use the ItemPhoto model's method to handle setting default
                        $record = $livewire->mountedTableActionRecord;
                        if ($record) {
                            $record->setAsDefault();
                            
                            // Refresh the state so it doesn't revert
                            $livewire->mountedTableAction = null;
                            $livewire->mountedTableActionRecord = null;
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('photo')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->width(100)
                    ->height(100)
                    ->square(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data) {
                        return $data;
                    })
                    ->using(function ($record, array $data) {
                        $record->update($data);
                        
                        // If setting as default, ensure we properly handle it
                        if (isset($data['is_default']) && $data['is_default']) {
                            $record->refresh()->setAsDefault();
                        }
                        
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('setAsDefault')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function ($record) {
                        $record->setAsDefault();
                    })
                    ->visible(fn ($record) => !$record->is_default),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order');
    }
} 