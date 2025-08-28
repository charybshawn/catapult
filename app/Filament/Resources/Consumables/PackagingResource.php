<?php

namespace App\Filament\Resources\Consumables;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\ConsumableResourceBase;
use App\Models\ConsumableType;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Forms\Components\Common as FormCommon;

class PackagingResource extends ConsumableResourceBase
{
    protected static ?string $navigationLabel = 'Packaging';
    protected static ?string $pluralLabel = 'Packaging';
    protected static ?string $modelLabel = 'Packaging';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 3;

    public static function getConsumableTypeCode(): string
    {
        return 'packaging';
    }

    protected static function getTypeSpecificFormSchema(bool $isEditMode): array
    {
        return [
            // Supplier field
            FormCommon::supplierSelect(),
            
            // Packaging type selection
            Select::make('packaging_type_id')
                ->label('Packaging Type')
                ->options(function () {
                    return PackagingType::where('is_active', true)
                        ->get()
                        ->mapWithKeys(function ($packagingType) {
                            return [$packagingType->id => $packagingType->display_name];
                        })
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set) {
                    // Get packaging type
                    $packagingType = PackagingType::find($state);
                    
                    // Set the name field from the packaging type
                    if ($packagingType) {
                        $set('name', $packagingType->name);
                    }
                }),
                
            // Hidden name field - will be set from the packaging type
            Hidden::make('name'),
        ];
    }

    protected static function getInventoryDetailsSchema(bool $isEditMode): array
    {
        return static::getStandardInventoryFields($isEditMode);
    }

    protected static function getTypeSpecificTableColumns(): array
    {
        return static::getPackagingSpecificColumns();
    }

    protected static function getTypeSpecificFilters(): array
    {
        return [
            SelectFilter::make('packaging_type_id')
                ->label('Packaging Type')
                ->options(function () {
                    return PackagingType::query()
                        ->where('is_active', true)
                        ->pluck('display_name', 'id')
                        ->toArray();
                })
                ->searchable(),
        ];
    }
}