<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedVarietyResource\Pages;
use App\Models\SeedVariety;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class SeedVarietyResource extends Resource
{
    protected static ?string $model = SeedVariety::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Seed Varieties';
    protected static ?string $navigationGroup = 'Inventory & Supplies';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Variety Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('crop_type')
                            ->label('Crop Type')
                            ->default('microgreens')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Variety Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop_type')
                    ->label('Crop Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (SeedVariety $record) {
                        // Check if this variety is used in any seed inventory
                        $hasInventory = $record->consumables()
                            ->where('type', 'seed')
                            ->where('is_active', true)
                            ->exists();
                            
                        if ($hasInventory) {
                            Notification::make()
                                ->title('Cannot Delete Seed Variety')
                                ->body('This seed variety is currently being used in active seed inventory. Please deactivate or delete the related seed inventory first.')
                                ->danger()
                                ->persistent()
                                ->send();
                                
                            throw new \Exception('Cannot delete seed variety with active inventory');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $hasInventory = false;
                            $varietiesWithInventory = [];
                            
                            foreach ($records as $record) {
                                if ($record->consumables()
                                    ->where('type', 'seed')
                                    ->where('is_active', true)
                                    ->exists()) {
                                    $hasInventory = true;
                                    $varietiesWithInventory[] = $record->name;
                                }
                            }
                            
                            if ($hasInventory) {
                                Notification::make()
                                    ->title('Cannot Delete Some Seed Varieties')
                                    ->body('The following varieties are currently being used in active seed inventory: ' . implode(', ', $varietiesWithInventory) . '. Please deactivate or delete the related seed inventory first.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                    
                                return false;
                            }
                            
                            return true;
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedVarieties::route('/'),
            'create' => Pages\CreateSeedVariety::route('/create'),
            'edit' => Pages\EditSeedVariety::route('/{record}/edit'),
        ];
    }
} 