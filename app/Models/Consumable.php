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
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\Log;
use App\Services\ConsumableCalculatorService;
use App\Traits\HasActiveStatus;
use App\Traits\HasSupplier;
use App\Traits\HasCostInformation;
use App\Traits\HasTimestamps;

class Consumable extends Model
{
    use HasFactory, LogsActivity, HasActiveStatus, HasSupplier, HasCostInformation, HasTimestamps;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        // 'type', // REMOVED: Legacy field, use consumable_type_id relationship instead
        'consumable_type_id',
        'supplier_id',
        'packaging_type_id', // For packaging consumables only
        'master_seed_catalog_id', // For seed consumables - references master catalog
        'master_cultivar_id', // For seed consumables - references master cultivar
        'cultivar', // For seed consumables - specific cultivar name (kept for backwards compatibility)
        'initial_stock',
        'consumed_quantity',
        'consumable_unit_id',
        'units_quantity', // How many units are in each package
        'restock_threshold',
        'restock_quantity',
        'cost_per_unit',
        'quantity_per_unit', // Weight of each unit
        'quantity_unit', // Unit of measurement (g, kg, l, oz)
        // 'unit', // DEPRECATED: Legacy enum field, use consumable_unit_id instead
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
        'cost_per_unit' => 'decimal:2', // Deprecated field, nullable
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
        // Removed 'initial_stock' since it's already a database field
    ];
    
    /**
     * Set the lot number to uppercase.
     */
    public function setLotNoAttribute($value)
    {
        $this->attributes['lot_no'] = $value ? strtoupper($value) : null;
    }
    
    /**
     * DEPRECATED: Set the type and automatically map to consumable_type_id.
     * This mutator is disabled to prevent setting the legacy enum field.
     */
    public function setTypeAttribute($value)
    {
        // DO NOT set the legacy type field anymore - it causes wrong defaults
        // The type should come from the consumableType relationship
        Log::warning('Attempted to set legacy type field on Consumable', [
            'value' => $value,
            'consumable_id' => $this->id ?? 'new',
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
    }
    
    /**
     * Get the type from the consumable_type relationship.
     * No fallback to legacy field - fails fast if relationship is missing.
     */
    public function getTypeAttribute($value)
    {
        if (!$this->consumableType) {
            throw new \RuntimeException("Consumable {$this->id} is missing consumable_type_id relationship. This must be fixed in the database.");
        }
        
        return $this->consumableType->code;
    }
    
    /**
     * Get the name attribute - computed for seed consumables, stored for others.
     */
    public function getNameAttribute($value)
    {
        // For seed consumables, compute name from master catalog + cultivar
        if ($this->consumableType && $this->consumableType->code === 'seed') {
            // If we have both master catalog and cultivar relationships
            if ($this->masterSeedCatalog && $this->masterCultivar) {
                $commonName = $this->masterSeedCatalog->common_name;
                $cultivarName = $this->masterCultivar->cultivar_name;
                
                // If common name and cultivar are the same, just show the name
                if (strtolower(trim($commonName)) === strtolower(trim($cultivarName))) {
                    return $commonName;
                }
                
                // Otherwise show both: "Common Name (Cultivar)"
                return $commonName . ' (' . $cultivarName . ')';
            }
            
            // Fallback: try to use stored cultivar field if relationships are missing
            if ($this->masterSeedCatalog && $this->cultivar) {
                $commonName = $this->masterSeedCatalog->common_name;
                $cultivarName = $this->cultivar;
                
                // If common name and cultivar are the same, just show the name
                if (strtolower(trim($commonName)) === strtolower(trim($cultivarName))) {
                    return $commonName;
                }
                
                // Otherwise show both: "Common Name (Cultivar)"
                return $commonName . ' (' . $cultivarName . ')';
            }
            
            // Final fallback: use stored name if relationships are incomplete
            return $value ?: 'Incomplete Seed Data';
        }
        
        // For non-seed consumables, use the stored name value
        return $value;
    }
    
    /**
     * Check if relationships are properly set for seed consumables.
     */
    public function hasValidSeedRelationships(): bool
    {
        if (!$this->consumableType || $this->consumableType->code !== 'seed') {
            return true; // Non-seed consumables don't need these relationships
        }
        
        // Seed consumables should have master catalog relationship
        if (!$this->master_seed_catalog_id || !$this->masterSeedCatalog) {
            return false;
        }
        
        // Should have either master_cultivar_id OR cultivar field
        if (!$this->master_cultivar_id && empty($this->cultivar)) {
            return false;
        }
        
        // If using master_cultivar_id, check that cultivar belongs to the assigned catalog
        if ($this->master_cultivar_id && $this->masterCultivar) {
            return $this->masterCultivar->master_seed_catalog_id === $this->master_seed_catalog_id;
        }
        
        return true;
    }
    
    /**
     * Get validation errors for seed relationships.
     */
    public function getSeedRelationshipErrors(): array
    {
        $errors = [];
        
        if (!$this->consumableType || $this->consumableType->code !== 'seed') {
            return $errors; // Non-seed consumables don't need validation
        }
        
        if (!$this->master_seed_catalog_id) {
            $errors[] = 'Seed consumable is missing master_seed_catalog_id';
        }
        
        if (!$this->masterSeedCatalog) {
            $errors[] = 'Seed consumable has invalid master_seed_catalog_id reference';
        }
        
        if (!$this->master_cultivar_id && empty($this->cultivar)) {
            $errors[] = 'Seed consumable is missing both master_cultivar_id and cultivar field';
        }
        
        // Check for cultivar-catalog mismatch
        if ($this->master_cultivar_id && $this->masterCultivar && $this->masterSeedCatalog) {
            if ($this->masterCultivar->master_seed_catalog_id !== $this->master_seed_catalog_id) {
                $errors[] = "Cultivar '{$this->masterCultivar->cultivar_name}' belongs to catalog '{$this->masterCultivar->masterSeedCatalog->common_name}' but consumable is assigned to catalog '{$this->masterSeedCatalog->common_name}'";
            }
        }
        
        return $errors;
    }
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Consumable $consumable) {
            // For seeds, we now use total_quantity directly (no calculation needed)
            if ($consumable->consumableType && $consumable->consumableType->code === 'seed') {
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
    
    // Supplier relationship is now provided by HasSupplier trait
    
    /**
     * Get the consumable type for this consumable.
     */
    public function consumableType(): BelongsTo
    {
        return $this->belongsTo(ConsumableType::class);
    }
    
    /**
     * Get the consumable unit for this consumable.
     */
    public function consumableUnit(): BelongsTo
    {
        return $this->belongsTo(ConsumableUnit::class);
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
     * Get the seed entry for this consumable.
     * Only applicable for seed consumables.
     */
    public function seedEntry(): BelongsTo
    {
        return $this->belongsTo(SeedEntry::class);
    }
    
    /**
     * Get the master seed catalog for this consumable.
     * Only applicable for seed consumables.
     */
    public function masterSeedCatalog(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class);
    }
    
    /**
     * Get the master cultivar for this consumable.
     * Only applicable for seed consumables.
     */
    public function masterCultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class);
    }
    
    
    
    /**
     * Get the display name for this consumable.
     * For packaging type consumables, the name is the packaging type name.
     * For seed type consumables, use the consumable name directly.
     */
    public function getDisplayNameAttribute(): string
    {
        return app(ConsumableCalculatorService::class)->formatDisplayName($this);
    }
    
    /**
     * Check if the consumable needs to be restocked.
     */
    public function needsRestock(): bool
    {
        return app(InventoryManagementService::class)->needsRestock($this);
    }
    
    /**
     * Calculate the total value of the current stock.
     */
    public function totalValue(): float
    {
        return app(InventoryManagementService::class)->calculateTotalValue($this);
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
        app(InventoryManagementService::class)->deductStock($this, $amount, $unit);
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
        return app(InventoryManagementService::class)->addStock($this, $amount, $unit, $lotNo);
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'consumable_type_id', 
                'supplier_id',
                'packaging_type_id',
                'master_seed_catalog_id',
                'master_cultivar_id',
                // 'cultivar', // DEPRECATED: use master_cultivar_id relationship instead
                'initial_stock',
                'consumed_quantity',
                'consumable_unit_id',
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
        return app(InventoryManagementService::class)->isOutOfStock($this);
    }

    /**
     * Get a formatted display of the total weight with unit.
     */
    public function getFormattedTotalWeightAttribute(): string
    {
        return app(InventoryManagementService::class)->getFormattedTotalWeight($this);
    }
    
    /**
     * Get the valid measurement units for quantity.
     */
    public static function getValidMeasurementUnits(): array
    {
        return app(ConsumableCalculatorService::class)->getValidMeasurementUnits();
    }
    
    /**
     * Get the valid types for consumables.
     */
    public static function getValidTypes(): array
    {
        return app(ConsumableCalculatorService::class)->getValidTypes();
    }
    
    /**
     * Get the valid unit types for inventory storage.
     */
    public static function getValidUnitTypes(): array
    {
        return app(ConsumableCalculatorService::class)->getValidUnitTypes();
    }

    /**
     * Get a formatted display of the stock.
     */
    public function getFormattedCurrentStockAttribute(): string
    {
        // For seed consumables, show the total quantity with its unit
        if ($this->consumableType && $this->consumableType->code === 'seed') {
            $unit = $this->quantity_unit ?? 'g';
            $displayUnit = $unit;
            $availableStock = $this->total_quantity;
            
            return "{$availableStock} {$displayUnit}";
        }
        
        // Use the consumable unit's symbol for display
        $displayUnit = $this->consumableUnit ? $this->consumableUnit->symbol : 'unit(s)';
        $availableStock = $this->current_stock; // This uses the accessor
        
        return "{$availableStock} {$displayUnit}";
    }

    /**
     * Get the computed current stock (initial - consumed).
     */
    public function getCurrentStockAttribute()
    {
        // For seeds, return available stock (total_quantity - consumed_quantity)
        if ($this->consumableType && $this->consumableType->code === 'seed') {
            return max(0, $this->total_quantity - $this->consumed_quantity);
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
                    return self::whereHas('consumableType', function($query) {
                            $query->where('code', 'seed');
                        })
                        ->where('is_active', true)
                        ->pluck('name')
                        ->unique()
                        ->toArray();
                }),
            Forms\Components\Select::make('supplier_id')
                ->label('Supplier')
                ->options(function () {
                    return Supplier::query()
                        ->with('supplierType')
                        ->whereHas('supplierType', function ($query) {
                            $query->whereIn('code', ['seed', 'other']);
                        })
                        ->orWhereNull('supplier_type_id')
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
                            Forms\Components\Hidden::make('supplier_type_id')
                                ->afterStateHydrated(function ($component, $state) {
                                    $seedType = \App\Models\SupplierType::where('code', 'seed')->first();
                                    $component->state($seedType?->id);
                                }),
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
            Forms\Components\Hidden::make('consumable_type_id')
                ->afterStateHydrated(function ($component, $state) {
                    $seedType = \App\Models\ConsumableType::findByCode('seed');
                    $component->state($seedType?->id);
                }),
            Forms\Components\Hidden::make('initial_stock')
                ->default(1),
            Forms\Components\Hidden::make('consumable_unit_id')
                ->afterStateHydrated(function ($component, $state) {
                    $unitType = \App\Models\ConsumableUnit::findByCode('unit');
                    $component->state($unitType?->id);
                }),
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
                ->helperText('Descriptive name for this soil type')
                ->required()
                ->maxLength(255),
                
            Forms\Components\Select::make('supplier_id')
                ->label('Supplier')
                ->options(function () {
                    return Supplier::query()
                        ->with('supplierType')
                        ->whereHas('supplierType', function ($query) {
                            $query->whereIn('code', ['soil', 'other']);
                        })
                        ->orWhereNull('supplier_type_id')
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
                            Forms\Components\Hidden::make('supplier_type_id')
                                ->afterStateHydrated(function ($component, $state) {
                                    $soilType = \App\Models\SupplierType::where('code', 'soil')->first();
                                    $component->state($soilType?->id);
                                }),
                            Forms\Components\Textarea::make('contact_info')
                                ->label('Contact Information')
                                ->rows(3),
                        ]);
                }),
                
            Forms\Components\Fieldset::make('Inventory Details')
                ->schema([
                    Forms\Components\TextInput::make('initial_stock')
                        ->label('Quantity')
                        ->helperText('Number of units in stock')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(1)
                        ->reactive()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                            // Update total quantity calculation when initial stock changes
                            if ($get('quantity_per_unit') && $get('quantity_per_unit') > 0) {
                                $set('total_quantity', (float)$get('initial_stock') * (float)$get('quantity_per_unit'));
                            }
                        }),
                        
                    Forms\Components\Select::make('consumable_unit_id')
                        ->label('Packaging Type')
                        ->helperText('Container or form of packaging')
                        ->options(\App\Models\ConsumableUnit::options())
                        ->required()
                        ->default(function () {
                            return \App\Models\ConsumableUnit::findByCode('unit')?->id;
                        }),
                        
                    Forms\Components\TextInput::make('quantity_per_unit')
                        ->label('Unit Size')
                        ->helperText('Capacity or size of each unit (e.g., 107L per bag)')
                        ->numeric()
                        ->minValue(0)
                        ->default(50)
                        ->step(0.01)
                        ->reactive()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                            // Update total quantity based on stock and unit size
                            $set('total_quantity', (float)$get('initial_stock') * (float)$get('quantity_per_unit'));
                        }),
                        
                    Forms\Components\Select::make('quantity_unit')
                        ->label('Unit of Measurement')
                        ->helperText('Unit for the size/capacity value')
                        ->options([
                            'l' => 'Liters',
                            'ml' => 'Milliliters',
                            'kg' => 'Kilograms',
                            'g' => 'Grams',
                            'lb' => 'Pounds',
                            'oz' => 'Ounces',
                            'cf' => 'Cubic Feet',
                            'cm' => 'Centimeters',
                            'm' => 'Meters',
                        ])
                        ->required()
                        ->default('l'),
                        
                    // Hidden field for total_quantity calculation
                    Forms\Components\Hidden::make('total_quantity')
                        ->default(0),
                        
                    // Hidden compatibility fields
                    Forms\Components\Hidden::make('consumed_quantity')
                        ->default(0),
                        
                    Forms\Components\Hidden::make('unit')
                        ->default('unit'),
                ])
                ->columns(2),
                
            Forms\Components\Fieldset::make('Restock Settings')
                ->schema([
                    Forms\Components\TextInput::make('restock_threshold')
                        ->label('Restock Threshold')
                        ->helperText('Minimum number of units to maintain in inventory')
                        ->numeric()
                        ->required()
                        ->default(2),
                        
                    Forms\Components\TextInput::make('restock_quantity')
                        ->label('Restock Quantity')
                        ->helperText('Quantity to order when restocking')
                        ->numeric()
                        ->required()
                        ->default(5),
                ])
                ->columns(2),
                
            Forms\Components\Textarea::make('notes')
                ->label('Additional Information')
                ->helperText('Any special notes about this soil type')
                ->rows(3),
                
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->helperText('Whether this soil is available for use')
                ->default(true),
                
            // Hidden fields to set correct type
            Forms\Components\Hidden::make('type')
                ->default('soil'),
                
            Forms\Components\Hidden::make('consumable_type_id')
                ->afterStateHydrated(function ($component, $state) {
                    $soilType = \App\Models\ConsumableType::where('code', 'soil')->first();
                    $component->state($soilType?->id);
                }),
        ];
    }

    /**
     * Get the seed variations associated with this consumable
     */
    public function seedVariations()
    {
        return $this->hasMany(SeedVariation::class);
    }

    /**
     * Get the consumable transactions for this consumable.
     */
    public function consumableTransactions(): HasMany
    {
        return $this->hasMany(ConsumableTransaction::class);
    }

    /**
     * Get the latest transaction for this consumable.
     */
    public function latestTransaction(): HasMany
    {
        return $this->hasMany(ConsumableTransaction::class)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Get current stock using transaction history if available.
     */
    public function getCurrentStockWithTransactions(): float
    {
        if ($this->consumableTransactions()->exists()) {
            return app(InventoryManagementService::class)->getCurrentStockFromTransactions($this);
        }

        // Fall back to legacy calculation
        return $this->getCurrentStockAttribute();
    }

    /**
     * Check if this consumable is using transaction-based tracking.
     */
    public function isUsingTransactionTracking(): bool
    {
        return $this->consumableTransactions()->exists();
    }

    /**
     * Initialize transaction tracking for this consumable.
     */
    public function initializeTransactionTracking(): ?ConsumableTransaction
    {
        return app(InventoryManagementService::class)->initializeTransactionTracking($this);
    }

    /**
     * Record consumption using transaction tracking.
     */
    public function recordConsumption(
        float $amount,
        ?string $unit = null,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): ConsumableTransaction {
        return app(InventoryManagementService::class)->recordConsumption(
            $this,
            $amount,
            $unit,
            $user,
            $referenceType,
            $referenceId,
            $notes,
            $metadata
        );
    }

    /**
     * Record addition using transaction tracking.
     */
    public function recordAddition(
        float $amount,
        ?string $unit = null,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): ConsumableTransaction {
        return app(InventoryManagementService::class)->recordAddition(
            $this,
            $amount,
            $unit,
            $user,
            $referenceType,
            $referenceId,
            $notes,
            $metadata
        );
    }

    /**
     * Get available seed options with stock filtering
     * Extends MasterSeedCatalog::getCombinedSelectOptions() to only show varieties with available stock
     */
    public static function getAvailableSeedSelectOptionsWithStock(): array
    {
        $options = [];

        $catalogs = MasterSeedCatalog::where('is_active', true)->get();

        foreach ($catalogs as $catalog) {
            $cultivars = $catalog->cultivars ?? [];

            if (!empty($cultivars) && is_array($cultivars)) {
                foreach ($cultivars as $cultivar) {
                    // Check if there are consumables with available stock for this catalog/cultivar combination
                    $availableStock = static::whereHas('consumableType', function ($query) {
                            $query->where('code', 'seed');
                        })
                        ->where('is_active', true)
                        ->where('master_seed_catalog_id', $catalog->id)
                        ->where(function ($query) use ($cultivar) {
                            // Match by cultivar name in the cultivar field or through master_cultivar relationship
                            $query->where('cultivar', $cultivar)
                                  ->orWhereHas('masterCultivar', function ($q) use ($cultivar) {
                                      $q->where('cultivar_name', $cultivar);
                                  });
                        })
                        ->whereRaw('(total_quantity - consumed_quantity) > 0')
                        ->sum(DB::raw('total_quantity - consumed_quantity'));

                    if ($availableStock > 0) {
                        $displayName = "{$catalog->common_name} ({$cultivar})";
                        $value = "{$catalog->id}:{$cultivar}";
                        $options[$value] = $displayName;
                    }
                }
            } else {
                // Fallback for entries without cultivars - check if there's available stock
                $availableStock = static::whereHas('consumableType', function ($query) {
                        $query->where('code', 'seed');
                    })
                    ->where('is_active', true)
                    ->where('master_seed_catalog_id', $catalog->id)
                    ->whereRaw('(total_quantity - consumed_quantity) > 0')
                    ->sum(DB::raw('total_quantity - consumed_quantity'));

                if ($availableStock > 0) {
                    $displayName = $catalog->common_name;
                    $options["{$catalog->id}:"] = $displayName;
                }
            }
        }

        return $options;
    }
}
