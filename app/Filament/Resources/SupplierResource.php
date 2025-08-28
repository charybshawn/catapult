<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\SupplierResource\Pages\ListSuppliers;
use App\Filament\Resources\SupplierResource\Pages\CreateSupplier;
use App\Filament\Resources\SupplierResource\Pages\EditSupplier;
use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\Supplier;
use App\Models\SupplierType;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;

/**
 * Supply chain and vendor management interface for agricultural operations.
 * 
 * Manages comprehensive supplier database for microgreens business including
 * seed suppliers, soil and growing media vendors, packaging suppliers, and
 * general consumable vendors. Provides integrated supplier relationship
 * management with contact tracking, performance monitoring, and procurement
 * workflow integration for reliable agricultural supply chain operations.
 * 
 * @filament_resource
 * @business_domain Agricultural supply chain management and vendor relationships
 * @workflow_support Supplier onboarding, contact management, performance tracking
 * @related_models Supplier, SupplierType, Consumable, SeedEntry, PurchaseOrder
 * @ui_features Type-based categorization, contact management, status tracking
 * @integration Inventory management, purchasing workflows, consumable tracking
 * @traits Uses standard resource traits for consistency and efficiency
 * 
 * Supplier Categories:
 * - Seed Suppliers: Specialized vendors providing microgreen seed varieties
 * - Soil Vendors: Growing media suppliers for substrates and amendments
 * - Packaging Suppliers: Container and packaging material vendors
 * - Consumable Vendors: General supplies, equipment, and production materials
 * - Service Providers: Specialized agricultural services and consultants
 * 
 * Agricultural Business Features:
 * - Seasonal supplier relationships with weather-dependent availability
 * - Quality certification tracking for organic and food safety compliance
 * - Lead time management for production planning and harvest scheduling
 * - Price variation tracking for cost optimization and budget planning
 * - Performance monitoring for supplier reliability and quality assessment
 * 
 * Supply Chain Operations:
 * - Supplier qualification and onboarding process for new vendors
 * - Contact management for purchasing, quality, and technical support
 * - Performance tracking including delivery reliability and quality metrics
 * - Integration with inventory management for automatic reorder calculations
 * - Procurement workflow support with supplier selection and comparison
 * - Contract management for volume pricing and exclusive supplier agreements
 * 
 * Business Process Integration:
 * - Automatic supplier selection based on inventory levels and reorder points
 * - Quality issue tracking integrated with supplier performance metrics
 * - Seasonal demand forecasting coordination with supplier capacity planning
 * - Cost optimization through supplier performance and pricing analysis
 * - Risk management through supplier diversification and backup relationships
 * 
 * @architecture Leverages standard resource traits for consistent functionality
 * @compliance Vendor qualification and performance tracking for agricultural standards
 */
class SupplierResource extends BaseResource
{
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    
    /** @var string The Eloquent model class for supplier management */
    protected static ?string $model = Supplier::class;

    /** @var string Navigation icon representing truck/delivery/supply concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';
    
    /** @var string Navigation label for supplier management */
    protected static ?string $navigationLabel = 'Suppliers';
    
    /** @var string Navigation group for inventory and supply chain resources */
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    /** @var int Third navigation position in inventory group */
    protected static ?int $navigationSort = 3;

    /**
     * Build the Filament form schema for supplier management and contact tracking.
     * 
     * Creates comprehensive supplier information form with supplier type
     * categorization, contact management, and business relationship details.
     * Form leverages standard resource sections for consistency while providing
     * supplier-specific functionality for agricultural supply chain management.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with supplier management and contact fields
     * @sections Basic information with supplier type selection and status
     * @contact_management Comprehensive contact information for procurement workflow
     * @additional_info Business details, terms, and relationship management
     * @defaults Sensible defaults for supplier type and status fields
     * @agricultural_context Supplier categorization supports agricultural supply needs
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getBasicInformationSection([
                    static::getNameField('Supplier Name'),
                        
                    Select::make('supplier_type_id')
                        ->label('Supplier Type')
                        ->relationship('supplierType', 'name')
                        ->options(SupplierType::options())
                        ->default(function () {
                            return SupplierType::findByCode('other')?->id;
                        })
                        ->required(),
                        
                    static::getActiveStatusField(),
                ]),
                
                static::getContactInformationSection(),
                
                static::getAdditionalInformationSection(),
            ]);
    }

    /**
     * Build the Filament data table for supplier overview and management.
     * 
     * Creates comprehensive supplier listing with type-based categorization,
     * contact information, and status indicators. Table design optimizes for
     * supplier selection during procurement workflows and provides quick access
     * to supplier contact information and performance status.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with supplier management features
     * @standard_configuration Leverages configureStandardTable for consistency
     * @type_visualization Color-coded supplier type badges for quick identification
     * @contact_access Essential contact information for procurement operations
     * @filtering Type and status filters for efficient supplier selection
     * @actions Standard CRUD actions with tooltips for clarity
     * @sorting Alphabetical default sort for easy supplier location
     * @agricultural_context Supplier types reflect agricultural supply categories
     */
    public static function table(Table $table): Table
    {
        return static::configureStandardTable(
            $table,
            columns: [
                static::getTextColumn('name', 'Name')
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                static::getStatusBadgeColumn(
                    field: 'supplierType.name',
                    label: 'Type',
                    colorMap: [
                        'Seeds' => 'success',
                        'Soil' => 'warning', 
                        'Packaging' => 'info',
                        'Consumables' => 'purple',
                    ]
                ),
                    
                static::getTextColumn('contact_name', 'Contact'),
                static::getTextColumn('contact_email', 'Email'),
                static::getTextColumn('contact_phone', 'Phone'),
                static::getActiveStatusColumn(),
            ],
            filters: [
                static::getRelationshipFilter('supplierType', 'Supplier Type'),
                static::getActiveStatusFilter(),
            ],
            actions: [
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View record'),
                    EditAction::make()
                        ->tooltip('Edit record'),
                    DeleteAction::make()
                        ->tooltip('Delete record'),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ]
        )->defaultSort('name', 'asc');
    }

    /**
     * Define the page routes and classes for supplier resource.
     * 
     * Provides standard CRUD workflow for supplier management with streamlined
     * interface focused on supplier onboarding and contact information
     * maintenance. No separate view page as edit provides comprehensive access
     * to all supplier data and relationship information.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes Standard create, list, edit workflow for supplier management
     * @workflow Optimized for supplier onboarding and relationship maintenance
     * @ui_pattern Edit-focused workflow reduces navigation complexity
     * @procurement Quick access to supplier information during purchasing operations
     */
    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
} 