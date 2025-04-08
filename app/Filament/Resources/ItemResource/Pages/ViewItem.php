<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Product Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Product Name'),
                                TextEntry::make('category.name')
                                    ->label('Category'),
                                TextEntry::make('active')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        '1' => 'success',
                                        '0' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => $state === '1' ? 'Active' : 'Inactive'),
                            ]),
                        
                        TextEntry::make('description')
                            ->columnSpanFull(),
                            
                        TextEntry::make('is_visible_in_store')
                            ->label('Store Visibility')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                '1' => 'success',
                                '0' => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => $state === '1' ? 'Visible in Store' : 'Hidden from Store'),
                    ]),
                    
                Section::make('Product Photos')
                    ->schema([
                        ViewEntry::make('photos')
                            ->view('filament.components.photo-gallery')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
    
    /**
     * Display relation managers
     */
    public function getRelationManagers(): array
    {
        return [
            ItemResource::getRelations()[0], // Price Variations Relation Manager
        ];
    }
} 