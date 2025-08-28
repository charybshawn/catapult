<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\CustomerResource\Forms\CustomerForm;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\Tables\CustomerTable;
use App\Models\Customer;
use Filament\Tables\Table;

/**
 * Customer relationship management interface for agricultural sales operations.
 * 
 * Manages comprehensive customer database for microgreens business including retail
 * consumers, wholesale accounts, restaurants, and institutional buyers. Provides
 * integrated customer management with pricing tiers, delivery preferences, order
 * history tracking, and agricultural compliance documentation.
 * 
 * @filament_resource
 * @business_domain Agricultural customer relationship management and sales
 * @workflow_support Customer onboarding, pricing management, order processing
 * @related_models Customer, CustomerType, Order, PriceVariation, User (login accounts)
 * @ui_features Customer type management, wholesale discount tracking, login account creation
 * @integration Order processing, pricing calculations, delivery scheduling
 * 
 * Customer Segmentation:
 * - Retail: Individual consumers purchasing through farmers markets or direct sales
 * - Wholesale: Grocery stores, restaurants, and food service establishments
 * - Institutional: Schools, hospitals, corporate cafeterias with volume contracts
 * - Distribution: Distributors and intermediaries in the supply chain
 * 
 * Agricultural Business Features:
 * - Customer type-based pricing with wholesale discount management
 * - Delivery address tracking for route optimization and freshness preservation
 * - Order history for seasonal demand analysis and crop planning integration
 * - Compliance tracking for organic certification and food safety requirements
 * - Communication preferences for harvest notifications and product availability
 * 
 * Business Operations:
 * - Automated pricing application based on customer type and volume commitments
 * - Integration with recurring order system for subscription-based deliveries  
 * - Customer portal account creation with order tracking and billing access
 * - Sales analytics and customer lifetime value calculations
 * - Territory management for delivery route optimization
 * 
 * @delegation Delegates to CustomerForm and CustomerTable for modular architecture
 */
class CustomerResource extends BaseResource
{
    /** @var string The Eloquent model class for agricultural customers */
    protected static ?string $model = Customer::class;

    /** @var string Navigation icon representing customer/user group concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    
    /** @var string Navigation group for customer-related resources */
    protected static string | \UnitEnum | null $navigationGroup = 'Customers';
    
    /** @var int Primary navigation position within customer group */
    protected static ?int $navigationSort = 1;

    /**
     * Build the Filament form schema for customer management.
     * 
     * Delegates to CustomerForm for comprehensive customer data collection including
     * business information, pricing tiers, delivery preferences, and login account
     * setup. Form adapts based on customer type with conditional fields for
     * wholesale accounts and institutional buyers.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with agricultural customer management fields
     * @delegation CustomerForm::schema() handles complex form logic and validations
     * @conditional_fields Form sections adapt based on customer type selection
     * @integration Supports customer portal account creation for order access
     * @business_rules Enforces customer type-specific field requirements
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(CustomerForm::schema());
    }

    /**
     * Build the Filament data table for customer overview and management.
     * 
     * Creates comprehensive customer listing with essential business information,
     * contact details, customer type indicators, and quick action access. Table
     * design optimizes for rapid customer lookup during order processing and
     * customer service operations.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with customer management features
     * @delegation CustomerTable handles columns, filters, and action definitions
     * @features Customer type badges, contact info, wholesale discount indicators
     * @sorting Display name sorting by business name then contact name
     * @search Multi-field search across business name, contact name, and email
     * @actions Quick access to edit customer details and order history
     * @performance Base configuration includes timestamp columns and defaults
     */
    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns([
                ...CustomerTable::columns(),
                ...static::getTimestampColumns(),
            ])
            ->filters(CustomerTable::filters())
            ->recordActions(CustomerTable::actions())
            ->toolbarActions(CustomerTable::bulkActions());
    }

    /**
     * Define relationship managers for customer resource.
     * 
     * Currently no relationship managers are configured as customer relationships
     * (orders, payments, deliveries) are typically managed through their respective
     * resources for better workflow separation and performance optimization.
     * 
     * @return array<class-string> Empty array - relationships managed in dedicated resources
     * @design_pattern Relationship access through dedicated resources prevents UI complexity
     * @performance Avoids loading heavy relationship data on customer management pages
     * @future_expansion Could include OrdersRelationManager for order history viewing
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Define the page routes and classes for customer resource.
     * 
     * Provides standard CRUD operations for customer management with streamlined
     * workflow focused on rapid customer setup and information maintenance.
     * No separate view page as edit page provides comprehensive access to all
     * customer data and related operations.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes Standard create, list, edit workflow for customer management
     * @workflow Optimized for quick customer onboarding and updates
     * @ui_pattern Edit-focused workflow reduces clicks for customer service operations
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
