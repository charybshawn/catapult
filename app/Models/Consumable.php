<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Filament\Forms; // Import Forms namespace
use App\Models\Supplier; // Import Supplier model

class Consumable extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type', // packaging, soil, seed, label, other
        'supplier_id',
        'packaging_type_id', // For packaging consumables only
        'seed_variety_id', // For seed consumables only
        'initial_stock',
        'consumed_quantity',
        'unit', // pieces, rolls, bags, etc.
        'units_quantity', // How many units are in each package
        'restock_threshold',
        'restock_quantity',
        'cost_per_unit',
        'quantity_per_unit', // Weight of each unit
        'quantity_unit', // Unit of measurement (g, kg, l, oz)
        'total_quantity',
        'notes',
        'lot_no',
        'is_active',
        'last_ordered_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'initial_stock' => 'decimal:3',
        'consumed_quantity' => 'decimal:3',
        'units_quantity' => 'integer',
        'restock_threshold' => 'decimal:3',
        'restock_quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'quantity_per_unit' => 'decimal:3',
        'total_quantity' => 'decimal:3',
        'is_active' => 'boolean',
        'last_ordered_at' => 'datetime',
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'current_stock',
    ];
    
    /**
     * Set the lot number to uppercase.
     */
    public function setLotNoAttribute($value)
    {
        $this->attributes['lot_no'] = $value ? strtoupper($value) : null;
    }
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Consumable $consumable) {
            // For seeds, we now use total_quantity directly (no calculation needed)
            if ($consumable->type === 'seed') {
                // The total_quantity field is now managed directly by the user
                return;
            }
            
            // For other consumable types, keep the previous calculation logic
            if ($consumable->quantity_per_unit) {
                $availableStock = max(0, $consumable->initial_stock - $consumable->consumed_quantity);
                $consumable->total_quantity = $availableStock * $consumable->quantity_per_unit;
            }
        });
    }
    
    /**
     * Get the supplier for this consumable.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the packaging type for this consumable.
     * Only applicable for packaging consumables.
     */
    public function packagingType(): BelongsTo
    {
        return $this->belongsTo(PackagingType::class);
    }
    
    /**
     * Get the seed variety for this consumable.
     * Only applicable for seed consumables.
     */
    public function seedVariety(): BelongsTo
    {
        return $this->belongsTo(SeedVariety::class);
    }
    
    /**
     * Get the display name for this consumable.
     * For packaging type consumables, the name is the packaging type name.
     * For seed type consumables, use the seed variety name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'seed' && $this->seedVariety) {
            return $this->seedVariety->name;
        }
        
        return $this->name;
    }
    
    /**
     * Check if the consumable needs to be restocked.
     */
    public function needsRestock(): bool
    {
        // For seeds, check based on total weight instead of unit count
        if ($this->type === 'seed') {
            return $this->needsSeedRestock();
        }
        
        // For all other consumables, check based on unit count
        return $this->current_stock <= $this->restock_threshold;
    }
    
    /**
     * Check if a seed consumable needs restocking based on total weight.
     */
    protected function needsSeedRestock(): bool
    {
        // For seeds, now directly check the total_quantity field
        return $this->total_quantity <= $this->restock_threshold;
    }
    
    /**
     * Calculate the total value of the current stock.
     */
    public function totalValue(): float
    {
        return $this->current_stock * ($this->cost_per_unit ?? 0);
    }
    
    /**
     * Helper method to normalize quantity based on unit
     * 
     * @param float $amount Amount to normalize
     * @param string|null $sourceUnit Source unit (if different from model's unit)
     * @return float Normalized amount
     */
    protected function normalizeQuantity(float $amount, ?string $sourceUnit = null): float
    {
        if (!$sourceUnit || $sourceUnit === $this->unit) {
            return $amount;
        }
        
        // Handle weight conversions
        if (in_array($this->unit, ['kg', 'g']) && in_array($sourceUnit, ['kg', 'g'])) {
            // Convert from g to kg
            if ($this->unit === 'kg' && $sourceUnit === 'g') {
                return $amount / 1000;
            }
            
            // Convert from kg to g
            if ($this->unit === 'g' && $sourceUnit === 'kg') {
                return $amount * 1000;
            }
        }
        
        // Handle volume conversions
        if (in_array($this->unit, ['l', 'ml']) && in_array($sourceUnit, ['l', 'ml'])) {
            // Convert from ml to l
            if ($this->unit === 'l' && $sourceUnit === 'ml') {
                return $amount / 1000;
            }
            
            // Convert from l to ml
            if ($this->unit === 'ml' && $sourceUnit === 'l') {
                return $amount * 1000;
            }
        }
        
        // Return original amount if no conversion is needed or possible
        return $amount;
    }
    
    /**
     * Deduct quantity from stock (increase consumed_quantity).
     * 
     * @param float $amount Amount to deduct
     * @param string|null $unit Unit of the amount (for conversion)
     */
    public function deduct(float $amount, ?string $unit = null): void
    {
        // Normalize amount based on unit if needed
        $normalizedAmount = $this->normalizeQuantity($amount, $unit);
        
        // For seed consumables, directly update total_quantity
        if ($this->type === 'seed') {
            $data = [
                'total_quantity' => max(0, $this->total_quantity - $normalizedAmount),
            ];
            
            // Still update consumed_quantity for consistency
            $data['consumed_quantity'] = $this->consumed_quantity + $normalizedAmount;
            
            $this->update($data);
            return;
        }
        
        // For other consumable types, use the original logic
        $newConsumedQuantity = $this->consumed_quantity + $normalizedAmount;
        
        $data = [
            'consumed_quantity' => $newConsumedQuantity,
        ];
        
        // Update total quantity if applicable
        if ($this->quantity_per_unit) {
            $availableStock = max(0, $this->initial_stock - $newConsumedQuantity);
            $data['total_quantity'] = $availableStock * $this->quantity_per_unit;
        }
        
        $this->update($data);
    }
    
    /**
     * Add quantity to stock (increase initial_stock).
     * 
     * @param float $amount Amount to add
     * @param string|null $unit Unit of the amount (for conversion)
     * @param string|null $lotNo Lot number for the new stock (for seeds)
     * @return bool True if added successfully, false if should create new record
     */
    public function add(float $amount, ?string $unit = null, ?string $lotNo = null): bool
    {
        // For seed consumables, check lot number first
        if ($this->type === 'seed' && $lotNo !== null) {
            // If this seed already has a lot number and it's different, 
            // return false to indicate a new record should be created
            if ($this->lot_no && strtoupper($lotNo) !== $this->lot_no) {
                return false;
            }
            
            // If record doesn't have a lot number yet, set it
            if (!$this->lot_no) {
                $this->lot_no = strtoupper($lotNo);
            }
        }
        
        // Normalize amount based on unit if needed
        $normalizedAmount = $this->normalizeQuantity($amount, $unit);
        
        // For seed consumables, directly update total_quantity
        if ($this->type === 'seed') {
            $data = [
                'total_quantity' => $this->total_quantity + $normalizedAmount,
                'last_ordered_at' => now(),
            ];
            
            // Still update initial_stock for consistency
            $data['initial_stock'] = $this->initial_stock + $normalizedAmount;
            
            $this->update($data);
            return true;
        }
        
        // For other consumable types, use the original logic
        $newInitialStock = $this->initial_stock + $normalizedAmount;
        
        $data = [
            'initial_stock' => $newInitialStock,
            'last_ordered_at' => now(),
        ];
        
        // Update total quantity if applicable
        if ($this->quantity_per_unit) {
            $availableStock = max(0, $newInitialStock - $this->consumed_quantity);
            $data['total_quantity'] = $availableStock * $this->quantity_per_unit;
        }
        
        $this->update($data);
        return true;
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'type', 
                'supplier_id',
                'packaging_type_id',
                'seed_variety_id',
                'initial_stock',
                'consumed_quantity',
                'unit',
                'restock_threshold',
                'restock_quantity',
                'cost_per_unit',
                'quantity_per_unit',
                'quantity_unit',
                'total_quantity',
                'is_active',
                'last_ordered_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Check if consumable is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Get a formatted display of the total weight with unit.
     */
    public function getFormattedTotalWeightAttribute(): string
    {
        // For packaging consumables, return empty string
        if ($this->type === 'packaging') {
            return '';
        }
        
        if (!$this->total_quantity || !$this->quantity_unit) {
            return '-';
        }
        
        return number_format($this->total_quantity, 2) . ' ' . $this->quantity_unit;
    }
    
    /**
     * Get the valid measurement units for quantity.
     */
    public static function getValidMeasurementUnits(): array
    {
        return [
            'g' => 'Grams',
            'kg' => 'Kilograms',
            'l' => 'Litre(s)',
            'ml' => 'Milliliters',
            'oz' => 'Ounces',
        ];
    }
    
    /**
     * Get the valid types for consumables.
     */
    public static function getValidTypes(): array
    {
        return [
            'packaging' => 'Packaging',
            'soil' => 'Soil',
            'seed' => 'Seeds',
            'label' => 'Labels',
            'other' => 'Other',
        ];
    }
    
    /**
     * Get the valid unit types for inventory storage.
     */
    public static function getValidUnitTypes(): array
    {
        return [
            'unit' => 'Unit(s)',
            'kg' => 'Kilograms',
            'g' => 'Grams',
            'oz' => 'Ounces',
            'l' => 'Litre(s)',
            'ml' => 'Milliliters',
        ];
    }

    /**
     * Get a formatted display of the stock.
     */
    public function getFormattedCurrentStockAttribute(): string
    {
        // For seed consumables, show the total quantity with its unit
        if ($this->type === 'seed') {
            $unit = $this->quantity_unit ?? 'g';
            $displayUnit = $unit;
            $availableStock = $this->total_quantity;
            
            return "{$availableStock} {$displayUnit}";
        }
        
        // Map unit codes to their full names for other consumables
        $unitMap = [
            'l' => 'litre(s)',
            'g' => 'gram(s)',
            'kg' => 'kilogram(s)',
            'oz' => 'ounce(s)',
            'unit' => 'unit(s)',
        ];
        
        $displayUnit = $unitMap[$this->unit] ?? $this->unit;
        $availableStock = $this->current_stock; // This uses the accessor
        
        return "{$availableStock} {$displayUnit}";
    }

    /**
     * Get the computed current stock (initial - consumed).
     */
    public function getCurrentStockAttribute()
    {
        // For seeds, return the total_quantity directly 
        if ($this->type === 'seed') {
            return $this->total_quantity;
        }
        
        // For other consumables, use the original calculation
        return max(0, $this->initial_stock - $this->consumed_quantity);
    }

    /**
     * Get the form schema for creating a Seed Consumable.
     *
     * @return array
     */
    public static function getSeedFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Seed Name/Variety')
                ->helperText('Include the variety name (e.g., "Basil - Genovese")')
                ->required()
                ->maxLength(255)
                ->datalist(function () {
                    return self::where('type', 'seed')
                        ->where('is_active', true)
                        ->pluck('name')
                        ->unique()
                        ->toArray();
                }),
            Forms\Components\Select::make('supplier_id')
                ->label('Supplier')
                ->options(function () {
                    return Supplier::query()
                        ->where(function ($query) {
                            $query->where('type', 'seed') // Changed to seed for seed suppliers
                                  ->orWhereNull('type')
                                  ->orWhere('type', 'other');
                        })
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                    return $action
                        ->form([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Hidden::make('type')
                                ->default('seed'),
                            Forms\Components\Textarea::make('contact_info')
                                ->label('Contact Information')
                                ->rows(3),
                        ]);
                }),
            // Simplified Quantity Tracking 
            Forms\Components\TextInput::make('total_quantity')
                ->label('Total Quantity')
                ->helperText('Total amount of seed (e.g., 3 for 3 KG)')
                ->numeric()
                ->minValue(0)
                ->required()
                ->default(0)
                ->step(0.001),
                
            Forms\Components\Select::make('quantity_unit')
                ->label('Unit of Measurement')
                ->helperText('Unit for the total amount')
                ->options([
                    'g' => 'Grams',
                    'kg' => 'Kilograms',
                    'oz' => 'Ounces',
                    'lb' => 'Pounds',
                ])
                ->required()
                ->default('g'),
                
            // Hidden fields to maintain compatibility
            Forms\Components\Hidden::make('initial_stock')
                ->default(1),
            Forms\Components\Hidden::make('unit')
                ->default('unit'),
            Forms\Components\Hidden::make('quantity_per_unit')
                ->default(1),
                
            // Restock Settings
            Forms\Components\TextInput::make('restock_threshold')
                ->label('Restock Threshold')
                ->helperText('When total quantity falls below this amount, reorder')
                ->numeric()
                ->required()
                ->default(500)
                ->step(0.001),
                
            Forms\Components\TextInput::make('restock_quantity')
                ->label('Restock Quantity')
                ->helperText('How much to order when restocking')
                ->numeric()
                ->required()
                ->default(1000)
                ->step(0.001),
                
            Forms\Components\TextInput::make('lot_no')
                ->label('Lot/Batch Number')
                ->helperText('Will be converted to uppercase')
                ->maxLength(100),
                
            Forms\Components\Textarea::make('notes')
                ->label('Additional Notes')
                ->helperText('Any additional information about this seed')
                ->rows(3),
                
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }

    /**
     * Get the form schema for creating a Soil Consumable.
     *
     * @return array
     */
    public static function getSoilFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Soil Name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('supplier_id')
                ->label('Supplier')
                ->options(function () {
                    return Supplier::query()
                        ->where(function ($query) {
                            $query->where('type', 'soil')
                                  ->orWhereNull('type')
                                  ->orWhere('type', 'other');
                        })
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                    return $action
                        ->form([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Hidden::make('type')
                                ->default('soil'),
                            Forms\Components\Textarea::make('contact_info')
                                ->label('Contact Information')
                                ->rows(3),
                        ]);
                }),
            Forms\Components\TextInput::make('initial_stock')
                ->label('Initial Stock')
                ->numeric()
                ->required()
                ->default(1),
            Forms\Components\TextInput::make('unit')
                ->label('Unit of Measurement')
                ->default('bags')
                ->required(),
            Forms\Components\TextInput::make('quantity_per_unit')
                ->label('Quantity Per Unit (L)')
                ->helperText('Amount of soil in liters per bag')
                ->numeric()
                ->required()
                ->default(50)
                ->minValue(0.01)
                ->step(0.01),
            Forms\Components\Hidden::make('quantity_unit')
                ->default('l'),
            // Hidden type is set in createOptionUsing
            Forms\Components\TextInput::make('cost_per_unit')
                ->label('Cost Per Unit ($)')
                ->numeric()
                ->prefix('$')
                ->required()
                ->default(0),
            Forms\Components\TextInput::make('restock_threshold')
                ->label('Restock Threshold (bags)')
                ->helperText('Minimum number of bags to maintain in inventory')
                ->numeric()
                ->required()
                ->default(2),
            Forms\Components\TextInput::make('restock_quantity')
                ->label('Restock Quantity')
                ->helperText('Quantity to order when restocking')
                ->numeric()
                ->required()
                ->default(5),
            Forms\Components\Textarea::make('notes')
                ->label('Additional Information')
                ->rows(3),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }
}
