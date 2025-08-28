<?php

namespace App\Models;

use RuntimeException;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
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

/**
 * Consumable Model for Agricultural Inventory Management
 * 
 * Manages all consumable materials required for microgreens production including
 * seeds, growing media (soil), packaging materials, and other operational supplies.
 * Provides comprehensive inventory tracking with lot management, supplier relationships,
 * and automated restock notifications for agricultural operations.
 * 
 * This model handles:
 * - Multi-type inventory management (seeds, soil, packaging, consumables)
 * - Lot-based tracking with uppercase normalization for consistency
 * - Supplier relationship management for procurement workflows
 * - Automated stock calculations and depletion tracking
 * - Restock threshold monitoring and automated alerts
 * - Transaction-based inventory history and audit trails
 * - Master seed catalog integration for variety management
 * 
 * Business Context:
 * Agricultural operations depend on consistent supply of quality materials:
 * - Seeds: Core input requiring lot tracking for quality and germination rates
 * - Growing media: Soil/coir blends affecting plant health and yield
 * - Packaging: Containers and labels for finished product presentation
 * - Consumables: Tools, nutrients, sanitizers, and operational supplies
 * 
 * Inventory management critical for:
 * - Production continuity and avoiding stockouts
 * - Quality control through lot tracking and supplier management
 * - Cost control through automated reordering and supplier comparison
 * - Regulatory compliance through complete audit trails
 * - Yield optimization through proper resource allocation
 * 
 * @property int $id Primary key
 * @property string|null $name Computed display name (auto-generated for seeds, stored for others)
 * @property int $consumable_type_id Type of consumable (seed, soil, packaging, consumable)
 * @property int|null $supplier_id Supplier providing this consumable
 * @property int|null $packaging_type_id Package type (for packaging consumables only)
 * @property int|null $master_seed_catalog_id Master seed variety (for seed consumables)
 * @property int|null $master_cultivar_id Specific cultivar (for seed consumables)
 * @property string|null $cultivar Legacy cultivar field (use master_cultivar_id instead)
 * @property float $initial_stock Initial quantity received/counted
 * @property float $consumed_quantity Amount consumed from initial stock
 * @property int|null $consumable_unit_id Unit type for packaging (bags, bottles, units)
 * @property int $units_quantity Number of units per package
 * @property float $restock_threshold Minimum stock level triggering reorder alerts
 * @property float $restock_quantity Recommended reorder quantity
 * @property float|null $cost_per_unit Legacy cost field (use HasCostInformation trait methods)
 * @property float $quantity_per_unit Weight/volume per individual unit
 * @property string $quantity_unit Measurement unit (g, kg, l, ml, etc.)
 * @property float $total_quantity Computed or direct total available quantity
 * @property string|null $notes Additional information and usage notes
 * @property string|null $lot_no Lot/batch identifier (auto-uppercased)
 * @property bool $is_active Whether consumable is available for use
 * @property \Carbon\Carbon|null $last_ordered_at Last procurement date
 * @property \Carbon\Carbon $created_at Consumable creation timestamp
 * @property \Carbon\Carbon $updated_at Last modification timestamp
 * 
 * @relationship consumableType BelongsTo relationship to ConsumableType lookup
 * @relationship supplier BelongsTo relationship to Supplier (via HasSupplier trait)
 * @relationship consumableUnit BelongsTo relationship to ConsumableUnit (packaging type)
 * @relationship packagingType BelongsTo relationship to PackagingType (for packaging consumables)
 * @relationship seedEntry BelongsTo relationship to SeedEntry (legacy)
 * @relationship masterSeedCatalog BelongsTo relationship to MasterSeedCatalog variety
 * @relationship masterCultivar BelongsTo relationship to MasterCultivar specification
 * @relationship seedVariations HasMany relationship to SeedVariations (pricing history)
 * @relationship consumableTransactions HasMany relationship to inventory transactions
 * 
 * @business_rules
 * - Lot numbers automatically converted to uppercase for consistency
 * - Seed consumables use computed names from master catalog + cultivar
 * - Non-seed consumables use stored names for flexibility
 * - Current stock = max(0, initial_stock - consumed_quantity) for non-seeds
 * - Seed consumables use total_quantity field directly (no calculation)
 * - Restock alerts triggered when current stock falls below threshold
 * - Transaction tracking provides detailed audit trails when enabled
 * - Seed relationships validated for data integrity and proper references
 * 
 * @workflow_patterns
 * Seed Inventory Management:
 * 1. Seeds received with lot numbers and supplier information
 * 2. Master catalog and cultivar relationships established
 * 3. Initial stock recorded with total quantity in specified units
 * 4. Production consumes seeds, reducing total quantity
 * 5. Restock alerts triggered when below threshold
 * 6. New lots create separate consumable records for tracking
 * 
 * General Consumable Workflow:
 * 1. Consumables received and catalogued with supplier details
 * 2. Initial stock and unit specifications recorded
 * 3. Consumption tracked through deduction methods
 * 4. Stock levels monitored against restock thresholds
 * 5. Automated alerts trigger procurement workflows
 * 6. Transaction history maintains complete audit trails
 * 
 * Transaction-Based Tracking:
 * 1. Initialize transaction tracking for detailed history
 * 2. Record all additions and consumptions as transactions
 * 3. Calculate current stock from transaction history
 * 4. Maintain audit trails for compliance and analysis
 * 
 * @agricultural_context
 * - Seeds: Quality degrades over time, lot tracking essential for germination rates
 * - Growing media: Batch consistency affects plant health and yield uniformity  
 * - Packaging: Container quality affects product presentation and shelf life
 * - Consumables: Tools and supplies support daily operations and maintenance
 * - Seasonal variations: Seed availability and pricing fluctuate with growing seasons
 * - Organic certification: Supplier tracking required for organic compliance
 * 
 * @performance_considerations
 * - Name computation cached for seed consumables to avoid repeated calculations
 * - Lot number indexing supports efficient lot-based queries
 * - Transaction tracking optional to balance detail vs performance
 * - Supplier relationships eager loaded for inventory reporting
 * - Activity logging tracks inventory changes for audit and analysis
 * 
 * @see \App\Services\InventoryManagementService For inventory operations and calculations
 * @see \App\Services\ConsumableCalculatorService For display formatting and validation
 * @see \App\Models\ConsumableTransaction For detailed transaction history
 * @see \App\Models\MasterSeedCatalog For seed variety specifications
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class Consumable extends Model
{
    use HasFactory, LogsActivity, HasActiveStatus, HasSupplier, HasCostInformation, HasTimestamps;
    
    /**
     * The attributes that are mass assignable.
     * 
     * Defines which consumable fields can be bulk assigned during creation
     * and updates, supporting agricultural inventory management workflows.
     * Includes all inventory parameters, relationships, and tracking fields.
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
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of decimal quantities for precise inventory
     * calculations, boolean flags for status tracking, and datetime stamps
     * for procurement and usage history.
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
     * 
     * Automatically normalizes lot numbers to uppercase for consistency
     * across the system. Critical for agricultural lot tracking where
     * case variations could cause lookup failures and quality control issues.
     * 
     * @param string|null $value Lot number to normalize
     * @business_context Consistent lot numbering essential for quality tracking
     * @usage Seed lot management and quality control workflows
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
            throw new RuntimeException("Consumable {$this->id} is missing consumable_type_id relationship. This must be fixed in the database.");
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
     * 
     * Compares current stock levels against defined restock thresholds
     * to trigger automated procurement alerts. Essential for maintaining
     * production continuity and avoiding stockouts in agricultural operations.
     * 
     * @return bool True if current stock below restock threshold
     * @business_context Prevents production delays from inventory shortages
     * @delegation Uses InventoryManagementService for consistent logic
     * @usage Automated restock alerts and procurement planning
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
     * 
     * Defines which consumable fields are tracked for audit and agricultural
     * compliance purposes. Logs changes to inventory levels, supplier relationships,
     * and critical parameters for quality control and regulatory compliance.
     * 
     * @return LogOptions Configured logging options for consumable changes
     * @business_context Inventory changes require complete audit trails
     * @compliance Required for agricultural quality control and traceability
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
        // For seeds, return the total_quantity directly 
        if ($this->consumableType && $this->consumableType->code === 'seed') {
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
            TextInput::make('name')
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
            Select::make('supplier_id')
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
                ->createOptionAction(function (Action $action) {
                    return $action
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Hidden::make('supplier_type_id')
                                ->afterStateHydrated(function ($component, $state) {
                                    $seedType = SupplierType::where('code', 'seed')->first();
                                    $component->state($seedType?->id);
                                }),
                            Textarea::make('contact_info')
                                ->label('Contact Information')
                                ->rows(3),
                        ]);
                }),
            // Simplified Quantity Tracking 
            TextInput::make('total_quantity')
                ->label('Total Quantity')
                ->helperText('Total amount of seed (e.g., 3 for 3 KG)')
                ->numeric()
                ->minValue(0)
                ->required()
                ->default(0)
                ->step(0.001),
                
            Select::make('quantity_unit')
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
            Hidden::make('consumable_type_id')
                ->afterStateHydrated(function ($component, $state) {
                    $seedType = ConsumableType::findByCode('seed');
                    $component->state($seedType?->id);
                }),
            Hidden::make('initial_stock')
                ->default(1),
            Hidden::make('consumable_unit_id')
                ->afterStateHydrated(function ($component, $state) {
                    $unitType = ConsumableUnit::findByCode('unit');
                    $component->state($unitType?->id);
                }),
            Hidden::make('quantity_per_unit')
                ->default(1),
                
            // Restock Settings
            TextInput::make('restock_threshold')
                ->label('Restock Threshold')
                ->helperText('When total quantity falls below this amount, reorder')
                ->numeric()
                ->required()
                ->default(500)
                ->step(0.001),
                
            TextInput::make('restock_quantity')
                ->label('Restock Quantity')
                ->helperText('How much to order when restocking')
                ->numeric()
                ->required()
                ->default(1000)
                ->step(0.001),
                
            TextInput::make('lot_no')
                ->label('Lot/Batch Number')
                ->helperText('Will be converted to uppercase')
                ->maxLength(100),
                
            Textarea::make('notes')
                ->label('Additional Notes')
                ->helperText('Any additional information about this seed')
                ->rows(3),
                
            Toggle::make('is_active')
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
            TextInput::make('name')
                ->label('Soil Name')
                ->helperText('Descriptive name for this soil type')
                ->required()
                ->maxLength(255),
                
            Select::make('supplier_id')
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
                ->createOptionAction(function (Action $action) {
                    return $action
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Hidden::make('supplier_type_id')
                                ->afterStateHydrated(function ($component, $state) {
                                    $soilType = SupplierType::where('code', 'soil')->first();
                                    $component->state($soilType?->id);
                                }),
                            Textarea::make('contact_info')
                                ->label('Contact Information')
                                ->rows(3),
                        ]);
                }),
                
            Fieldset::make('Inventory Details')
                ->schema([
                    TextInput::make('initial_stock')
                        ->label('Quantity')
                        ->helperText('Number of units in stock')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(1)
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get) {
                            // Update total quantity calculation when initial stock changes
                            if ($get('quantity_per_unit') && $get('quantity_per_unit') > 0) {
                                $set('total_quantity', (float)$get('initial_stock') * (float)$get('quantity_per_unit'));
                            }
                        }),
                        
                    Select::make('consumable_unit_id')
                        ->label('Packaging Type')
                        ->helperText('Container or form of packaging')
                        ->options(ConsumableUnit::options())
                        ->required()
                        ->default(function () {
                            return ConsumableUnit::findByCode('unit')?->id;
                        }),
                        
                    TextInput::make('quantity_per_unit')
                        ->label('Unit Size')
                        ->helperText('Capacity or size of each unit (e.g., 107L per bag)')
                        ->numeric()
                        ->minValue(0)
                        ->default(50)
                        ->step(0.01)
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get) {
                            // Update total quantity based on stock and unit size
                            $set('total_quantity', (float)$get('initial_stock') * (float)$get('quantity_per_unit'));
                        }),
                        
                    Select::make('quantity_unit')
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
                    Hidden::make('total_quantity')
                        ->default(0),
                        
                    // Hidden compatibility fields
                    Hidden::make('consumed_quantity')
                        ->default(0),
                        
                    Hidden::make('unit')
                        ->default('unit'),
                ])
                ->columns(2),
                
            Fieldset::make('Restock Settings')
                ->schema([
                    TextInput::make('restock_threshold')
                        ->label('Restock Threshold')
                        ->helperText('Minimum number of units to maintain in inventory')
                        ->numeric()
                        ->required()
                        ->default(2),
                        
                    TextInput::make('restock_quantity')
                        ->label('Restock Quantity')
                        ->helperText('Quantity to order when restocking')
                        ->numeric()
                        ->required()
                        ->default(5),
                ])
                ->columns(2),
                
            Textarea::make('notes')
                ->label('Additional Information')
                ->helperText('Any special notes about this soil type')
                ->rows(3),
                
            Toggle::make('is_active')
                ->label('Active')
                ->helperText('Whether this soil is available for use')
                ->default(true),
                
            // Hidden fields to set correct type
            Hidden::make('type')
                ->default('soil'),
                
            Hidden::make('consumable_type_id')
                ->afterStateHydrated(function ($component, $state) {
                    $soilType = ConsumableType::where('code', 'soil')->first();
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
}
