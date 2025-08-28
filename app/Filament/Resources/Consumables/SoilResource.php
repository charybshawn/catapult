<?php

namespace App\Filament\Resources\Consumables;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\ConsumableResourceBase;
use App\Models\ConsumableType;
use App\Models\Consumable;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Forms\Components\Common as FormCommon;

class SoilResource extends ConsumableResourceBase
{
    protected static ?string $navigationLabel = 'Soil & Growing Media';
    protected static ?string $pluralLabel = 'Soil & Growing Media';
    protected static ?string $modelLabel = 'Soil/Growing Media';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?int $navigationSort = 4;

    public static function getConsumableTypeCode(): string
    {
        return 'soil';
    }

    protected static function getTypeSpecificFormSchema(bool $isEditMode): array
    {
        return [
            // Supplier field
            FormCommon::supplierSelect(),
            
            // Text input for soil name with autocomplete
            TextInput::make('name')
                ->label('Soil/Media Name')
                ->required()
                ->maxLength(255)
                ->datalist(function () {
                    return Consumable::whereHas('consumableType', fn($q) => $q->where('code', 'soil'))
                        ->where('is_active', true)
                        ->pluck('name')
                        ->unique()
                        ->toArray();
                }),
        ];
    }

    protected static function getInventoryDetailsSchema(bool $isEditMode): array
    {
        return static::getStandardInventoryFields($isEditMode);
    }

    protected static function getTypeSpecificTableColumns(): array
    {
        return [
            TextColumn::make('quantity_per_unit')
                ->label('Unit Size')
                ->formatStateUsing(fn($state, $record) => $state ? "{$state} {$record->quantity_unit}" : '-')
                ->sortable()
                ->toggleable(),
        ];
    }

    protected static function getTypeSpecificFilters(): array
    {
        return [
            SelectFilter::make('quantity_unit')
                ->label('Unit of Measurement')
                ->options([
                    'l' => 'Liters',
                    'ml' => 'Milliliters',
                    'kg' => 'Kilograms',
                    'g' => 'Grams',
                ])
                ->searchable(),
        ];
    }
}