<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\SlideOverConfigurations;
use App\Filament\Traits\HasConsistentSlideOvers;
use App\Filament\Traits\CsvExportAction;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Staff and access management interface for agricultural operations personnel.
 * 
 * Manages comprehensive employee database for microgreens business including
 * production staff, managers, and administrators. Provides integrated user
 * account management with role-based permissions, time tracking integration,
 * and access control for agricultural production systems and data security.
 * 
 * @filament_resource
 * @business_domain Agricultural workforce management and access control
 * @workflow_support Employee onboarding, role management, permission control, time tracking
 * @related_models User, Role, Permission, TimeCard, Harvest, CropPlan
 * @ui_features Role-based access control, slide-over interface, CSV export, time tracking integration
 * @security Permission-based action visibility, password management, email verification
 * @integration Time tracking, activity logging, production task assignment
 * @traits Uses slide-over configurations and CSV export for enhanced functionality
 * 
 * Employee Categories and Access Levels:
 * - Production Staff (User): Basic access to production tasks, time tracking, harvest recording
 * - Supervisors (Manager): Enhanced access to planning, quality control, staff coordination
 * - Operations Managers (Admin): Full access to all systems, reporting, configuration
 * - Specialized Roles: Custom permissions for specific agricultural functions
 * 
 * Agricultural Workforce Management:
 * - Seasonal staffing management with temporary and permanent employees
 * - Production task assignment based on skills and certifications
 * - Harvest team coordination and performance tracking
 * - Quality control responsibilities and accountability tracking
 * - Safety certification and training record maintenance
 * 
 * Access Control and Security:
 * - Role-based permissions for different agricultural operations areas
 * - Data access control for sensitive business and customer information
 * - Production system access based on training and certification levels
 * - Time-based access restrictions for seasonal operations
 * - Integration with customer-facing portal permissions (excluded from this interface)
 * 
 * Business Operations Integration:
 * - Time tracking integration for labor cost analysis and productivity monitoring
 * - Production task assignment and completion tracking
 * - Quality control checkpoints with individual accountability
 * - Harvest attribution for performance analysis and quality tracking
 * - Training and certification tracking for compliance and safety
 * - Seasonal workforce planning and shift coordination
 * 
 * HR and Administrative Features:
 * - Employee contact information and communication preferences
 * - Email verification for system security and communication
 * - Password management with security policies
 * - Role transition management for promotions and cross-training
 * - Activity logging for security audit and performance review
 * 
 * @architecture Leverages traits for slide-over UI and CSV export functionality
 * @security Role-based action visibility and data access control
 * @separation Customer users managed separately through customer portal system
 */
class UserResource extends BaseResource
{
    use HasConsistentSlideOvers, CsvExportAction;

    /** @var string The Eloquent model class for staff and employee management */
    protected static ?string $model = User::class;

    /** @var string Navigation icon representing users/people concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    /** @var string Navigation label emphasizing employee context */
    protected static ?string $navigationLabel = 'Employees';

    /** @var string Navigation group for system administration */
    protected static string | \UnitEnum | null $navigationGroup = 'System';

    /** @var int Fifth navigation position for administrative functions */
    protected static ?int $navigationSort = 5;

    /**
     * Build the Filament form schema for employee account management.
     * 
     * Creates comprehensive employee onboarding and management form with contact
     * information, role assignment, and security configuration. Form provides
     * role-based access control setup and password management with appropriate
     * security measures for agricultural production system access.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with employee management and security fields
     * @sections Employee information with contact details and access control
     * @security Password hashing, email verification, role-based permissions
     * @roles Hierarchical role system (User, Manager, Admin) with clear descriptions
     * @validation Email uniqueness, required fields, password requirements
     * @agricultural_context Role descriptions reflect agricultural operations hierarchy
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employee Information')
                    ->description('Basic employee details')
                    ->schema([
                        static::getNameField('Full Name'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                    ])->columns(3),
                
                Section::make('Access & Permissions')
                    ->description('Configure employee access level')
                    ->schema([
                        Select::make('roles')
                            ->label('Employee Role')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->options([
                                'user' => 'User (Basic Access)',
                                'manager' => 'Manager (Enhanced Access)',
                                'admin' => 'Admin (Full Access)',
                            ])
                            ->default(['user'])
                            ->required()
                            ->helperText('Select the appropriate access level'),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Leave blank to keep existing password'),
                        Toggle::make('email_verified')
                            ->label('Email Verified')
                            ->default(true)
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $state, $record) => 
                                $component->state($record?->email_verified_at !== null)
                            ),
                    ])->columns(1),
            ]);
    }

    /**
     * Build the Filament data table for employee management and monitoring.
     * 
     * Creates comprehensive employee listing with role indicators, contact
     * information, and activity metrics. Table excludes customer users to focus
     * on internal staff management with role-based action visibility and
     * time tracking integration for workforce management.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with employee management features
     * @query_filtering Excludes customer users to focus on internal staff
     * @role_visualization Color-coded role badges for quick access level identification
     * @security Admin-only delete permissions with self-protection
     * @integration Time card count displays for productivity monitoring
     * @export CSV export with role and time tracking data
     * @agricultural_context Role colors reflect agricultural operations hierarchy
     */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer')))
            ->columns([
                static::getNameColumn(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'user' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('timeCards_count')
                    ->label('Time Cards')
                    ->counts('timeCards')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn (Builder $query) => $query->whereNot('name', 'customer'))
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View record'),
                    EditAction::make()
                        ->tooltip('Edit record'),
                    DeleteAction::make()
                        ->tooltip('Delete record')
                        ->visible(fn (User $record) => auth()->user()->hasRole('admin') && $record->id !== auth()->id()),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('admin')),
                ]),
            ]);
    }

    /**
     * Define relationship managers for user resource.
     * 
     * No relationship managers configured as user relationships (time cards,
     * activities, role assignments) are managed through their respective
     * specialized resources to maintain clear separation of concerns and
     * optimize performance for employee management workflows.
     * 
     * @return array<class-string> Empty array - relationships managed in dedicated resources
     * @workflow_focus Employee management concentrates on user account and role administration
     * @design_pattern User relationships accessible through dedicated time tracking and activity resources
     * @performance Avoids complex relationship loading on employee management pages
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Define the page routes and classes for user resource.
     * 
     * Provides streamlined employee management workflow focused on list view
     * and creation capabilities. No separate edit or view pages as employee
     * management utilizes slide-over interface for efficient user account
     * administration without page navigation complexity.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes List and create workflow with slide-over edit interface
     * @ui_pattern Slide-over interface reduces navigation complexity for employee management
     * @efficiency Quick employee account setup and modification through slide-over forms
     * @traits Leverages HasConsistentSlideOvers for optimized user experience
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
        ];
    }
    
    /**
     * Define CSV export columns for employee data reporting and analysis.
     * 
     * Configures comprehensive employee data export including role assignments
     * and time tracking information for HR reporting, workforce analysis, and
     * compliance documentation. Uses automatic schema detection enhanced with
     * relationship data for complete employee context.
     * 
     * @return array Export column definitions with role and time tracking context
     * @includes Role assignments for access level analysis
     * @time_tracking Time card data for productivity and labor cost analysis
     * @hr_reporting Comprehensive employee information for administrative purposes
     * @compliance Employee data export for regulatory and audit requirements
     */
    protected static function getCsvExportColumns(): array
    {
        // Get automatically detected columns from database schema
        $autoColumns = static::getColumnsFromSchema();
        
        // Add relationship columns
        return static::addRelationshipColumns($autoColumns, [
            'roles' => ['name'],
            'timeCards' => ['date', 'hours_worked', 'overtime_hours'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export for complete employee context.
     * 
     * Ensures exported employee data includes critical role information necessary
     * for access level analysis, security audits, and workforce management
     * reporting essential for agricultural operations oversight.
     * 
     * @return array<string> Relationship names to eager load for export
     * @relationships Roles for access control and security analysis
     * @performance Prevents N+1 queries during employee data exports
     * @security Role information essential for access control audits
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['roles'];
    }
    
    /**
     * Custom query for CSV export filtering to exclude customer accounts.
     * 
     * Provides specialized query for employee-focused export that excludes
     * customer portal users, ensuring export data contains only internal
     * staff information for workforce analysis and HR reporting purposes.
     * 
     * @return Builder Configured query excluding customer role users
     * @filtering Excludes customer users for internal staff reporting focus
     * @performance Eager loads roles for export efficiency
     * @separation Maintains clear distinction between staff and customer data
     */
    protected static function getTableQuery(): Builder
    {
        return static::getModel()::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'))
            ->with(['roles']);
    }
} 