<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\HasTimestamps;

/**
 * Supplier Model for Agricultural Supply Chain Management
 * 
 * Manages suppliers providing essential materials for microgreens production
 * including seeds, growing media (soil/coconut coir), packaging materials,
 * and other consumables required for agricultural operations.
 * 
 * This model handles:
 * - Seed suppliers providing agricultural varieties for production
 * - Soil/growing media suppliers for crop cultivation
 * - Packaging suppliers for product containers and labeling
 * - Consumable suppliers for tools, nutrients, and other materials
 * - Contact management and vendor relationship tracking
 * 
 * Business Context:
 * Agricultural microgreens operations depend on consistent, high-quality supplies
 * from specialized vendors. Seed quality directly impacts crop yields, growing
 * media affects germination rates, and packaging influences product presentation.
 * Supplier management is critical for:
 * - Maintaining consistent product quality
 * - Managing seasonal availability and pricing
 * - Tracking vendor performance and delivery reliability
 * - Supporting crop planning with supply availability
 * 
 * @property int $id Primary key
 * @property string $name Supplier company name
 * @property int $supplier_type_id Type of supplier (seed, soil, packaging, consumable, other)
 * @property string|null $contact_name Primary contact person at supplier
 * @property string|null $contact_email Email for orders and communications
 * @property string|null $contact_phone Phone number for urgent communications
 * @property string|null $address Supplier physical/mailing address
 * @property string|null $notes Additional notes about supplier terms, quality, etc.
 * @property bool $is_active Whether supplier is currently available for orders
 * @property \Carbon\Carbon $created_at Supplier registration date
 * @property \Carbon\Carbon $updated_at Last supplier information update
 * 
 * @relationship supplierType BelongsTo relationship to SupplierType lookup (seed/soil/packaging/consumable/other)
 * @relationship soilRecipes HasMany relationship to Recipes using this supplier for soil
 * @relationship seedEntries HasMany relationship to SeedEntries from this supplier
 * @relationship inventoryItems HasMany relationship to Inventory items from this supplier
 * 
 * @business_rules
 * - Suppliers must have a valid supplier_type_id for categorization
 * - Contact information is optional but recommended for active suppliers
 * - Inactive suppliers (is_active = false) should not be used for new orders
 * - Supplier types determine which products/materials they can provide
 * - Each supplier type has specific business logic and relationships
 * 
 * @workflow_patterns
 * Seed Supplier Workflow:
 * 1. New seed varieties discovered and evaluated
 * 2. Supplier registered with contact information and type 'seed'
 * 3. SeedEntries created linking varieties to this supplier
 * 4. Price tracking and availability monitoring
 * 5. Order placement and delivery coordination
 * 
 * Soil Supplier Integration:
 * 1. Growing recipes specify soil requirements
 * 2. Soil suppliers linked to recipes via supplier_soil_id
 * 3. Inventory tracking for soil/growing media quantities
 * 4. Quality monitoring and crop performance correlation
 * 
 * @agricultural_context
 * - Seed suppliers: Companies providing microgreens seeds (Johnny's Seeds, True Leaf Market)
 * - Soil suppliers: Growing media providers (coconut coir, peat moss, custom blends)
 * - Packaging suppliers: Container and label providers for finished products
 * - Consumable suppliers: Tools, nutrients, sanitizers, and other materials
 * 
 * @see \App\Models\SupplierType For supplier categorization and business rules
 * @see \App\Models\SeedEntry For seed variety sourcing from suppliers
 * @see \App\Models\Recipe For soil supplier integration with growing methods
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class Supplier extends Model
{
    use HasFactory, LogsActivity, HasActiveStatus, HasTimestamps;
    
    /**
     * The attributes that are mass assignable.
     * 
     * Defines which supplier fields can be bulk assigned during creation
     * and updates, supporting agricultural supply chain management workflows.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'supplier_type_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'notes',
        'is_active',
    ];
    
    /**
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of boolean flags for supplier status
     * and availability tracking in agricultural supply management.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the supplier type for this supplier.
     * 
     * Returns the categorization of this supplier (seed, soil, packaging, 
     * consumable, other) which determines business rules, relationships,
     * and available products for agricultural operations.
     * 
     * @return BelongsTo<SupplierType, Supplier> Supplier category relationship
     * @business_context Determines which agricultural materials this supplier provides
     * @usage Controls UI display, filtering, and business logic routing
     */
    public function supplierType(): BelongsTo
    {
        return $this->belongsTo(SupplierType::class);
    }

    /**
     * Get the recipes where this supplier provides soil.
     * 
     * Returns all growing recipes that specify this supplier as the source
     * for soil or growing media. Used for tracking which crops depend on
     * this supplier's growing media and for supply planning.
     * 
     * @return HasMany<Recipe> Recipes using this supplier for soil/growing media
     * @business_context Links soil suppliers to specific growing methodologies
     * @performance Enables batch queries for recipe-supplier dependencies
     * @usage Supply planning and vendor impact analysis for crop production
     */
    public function soilRecipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'supplier_soil_id');
    }
    
    /**
     * Get the seed entries from this supplier.
     * 
     * Returns all seed varieties sourced from this supplier, including
     * pricing history, availability, and variety specifications. Essential
     * for seed inventory management and supplier performance tracking.
     * 
     * @return HasMany<SeedEntry> Seed varieties from this supplier
     * @business_context Links seed suppliers to available microgreens varieties
     * @performance Supports eager loading for supplier-seed catalog displays
     * @usage Seed ordering, price tracking, and variety availability management
     */
    public function seedEntries(): HasMany
    {
        return $this->hasMany(SeedEntry::class);
    }
    
    /**
     * Get the inventory items from this supplier.
     * 
     * Returns all inventory items (seeds, soil, packaging, consumables)
     * sourced from this supplier. Supports comprehensive supply chain
     * tracking and vendor relationship management.
     * 
     * @return HasMany<Inventory> Inventory items from this supplier  
     * @business_context Tracks all materials sourced from supplier across categories
     * @performance Enables supplier-wide inventory queries and reporting
     * @usage Vendor performance analysis and supply chain optimization
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Check if this supplier is a soil supplier.
     * 
     * Determines if supplier provides growing media (soil, coconut coir,
     * peat moss) for agricultural production. Used for filtering suppliers
     * when selecting growing media for recipes and inventory management.
     * 
     * @return bool True if supplier provides soil/growing media
     * @business_context Growing media quality directly affects germination rates
     * @usage Recipe configuration and soil inventory management workflows
     */
    public function isSoilSupplier(): bool
    {
        return $this->supplierType?->isSoil() ?? false;
    }

    /**
     * Check if this supplier is a seed supplier.
     * 
     * Determines if supplier provides seed varieties for microgreens production.
     * Used for filtering suppliers during seed catalog management, ordering,
     * and variety planning for agricultural operations.
     * 
     * @return bool True if supplier provides seeds
     * @business_context Seed quality and variety selection impacts crop yields
     * @usage Seed ordering workflows and variety catalog management
     */
    public function isSeedSupplier(): bool
    {
        return $this->supplierType?->isSeed() ?? false;
    }

    /**
     * Check if this supplier is a consumable supplier.
     * 
     * Determines if supplier provides consumable materials (nutrients,
     * sanitizers, tools) for agricultural operations. Used for operational
     * supply management and maintenance workflows.
     * 
     * @return bool True if supplier provides consumables
     * @business_context Consumables support daily operations and crop care
     * @usage Operations planning and consumable inventory management
     */
    public function isConsumableSupplier(): bool
    {
        return $this->supplierType?->isConsumable() ?? false;
    }

    /**
     * Check if this supplier is a packaging supplier.
     * 
     * Determines if supplier provides packaging materials (containers, labels,
     * bags) for finished microgreens products. Critical for product presentation
     * and customer delivery workflows.
     * 
     * @return bool True if supplier provides packaging materials
     * @business_context Packaging affects product presentation and shelf life
     * @usage Packaging inventory management and product fulfillment workflows
     */
    public function isPackagingSupplier(): bool
    {
        return $this->supplierType?->isPackaging() ?? false;
    }

    /**
     * Check if this supplier is an other supplier.
     * 
     * Determines if supplier provides miscellaneous materials not covered
     * by standard categories (equipment, services, specialty items).
     * Catch-all category for non-standard agricultural supply needs.
     * 
     * @return bool True if supplier provides other/miscellaneous materials
     * @business_context Covers specialized equipment and services
     * @usage General supply management and vendor relationship tracking
     */
    public function isOtherSupplier(): bool
    {
        return $this->supplierType?->isOther() ?? false;
    }

    /**
     * Configure the activity log options for this model.
     * 
     * Defines which supplier fields are tracked for audit and supply chain
     * transparency. Logs changes to contact information, supplier type, and
     * status for vendor relationship management and compliance.
     * 
     * @return LogOptions Configured logging options for supplier changes
     * @business_context Required for supply chain audits and vendor management
     * @compliance Tracks supplier relationship changes and contact updates
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'supplier_type_id', 'contact_name', 'contact_email', 'contact_phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
