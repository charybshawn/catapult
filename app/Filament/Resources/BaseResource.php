<?php

namespace App\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Base Resource for Agricultural Operations
 * 
 * Abstract foundation class for all Filament resources in the Catapult agricultural
 * management system. Provides standardized form components, table configurations,
 * and common agricultural business patterns for microgreens farm operations.
 * 
 * @filament_base_resource Foundation for all Catapult Filament resources
 * @agricultural_standards Common patterns for farm management interfaces
 * @ui_consistency Standardized components, actions, and table behaviors
 * @performance_optimization Session persistence, query optimization patterns
 * 
 * @package App\Filament\Resources
 * @author Catapult Development Team
 * @version 1.0.0
 */
abstract class BaseResource extends Resource
{
    /**
     * Configure default table settings with persistence
     * 
     * Applies standard table configuration including session persistence for filters,
     * column searches, and general search state. Provides striped styling for
     * improved readability across all agricultural resource tables.
     * 
     * @filament_table_config Standard table persistence and styling
     * @session_persistence Maintains user preferences across sessions
     * @ui_standards Consistent table appearance for agricultural data
     * 
     * @param Table $table Filament table instance to configure
     * @return Table Configured table with persistence and styling
     */
    public static function configureTableDefaults(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->striped();
    }
    
    /**
     * Configure standard table with common features
     * 
     * Builds a complete table configuration combining custom columns, filters, and actions
     * with standardized agricultural resource patterns. Merges provided components with
     * default components based on model capabilities (active status, timestamps, etc.).
     * 
     * @filament_table_builder Complete table configuration system
     * @agricultural_patterns Standard columns, filters, and actions for farm data
     * @component_merging Combines custom and standard components intelligently
     * @model_introspection Adds components based on model trait capabilities
     * 
     * @param Table $table Filament table instance to configure
     * @param array $columns Custom columns to include in table
     * @param array $filters Custom filters to include in table
     * @param array $actions Custom record actions (empty array uses standard)
     * @param array $bulkActions Custom bulk actions to merge with standard
     * @return Table Fully configured table with all features
     */
    public static function configureStandardTable(Table $table, array $columns = [], array $filters = [], array $actions = [], array $bulkActions = []): Table
    {
        $standardColumns = static::getStandardTableColumns();
        $standardFilters = static::getStandardTableFilters();
        $standardActions = static::getStandardTableActions();
        $standardBulkActions = static::getStandardBulkActions();
        
        return static::configureTableDefaults($table)
            ->columns(array_merge(
                $columns,
                $standardColumns
            ))
            ->filters(array_merge(
                $filters,
                $standardFilters
            ))
            ->recordActions(empty($actions) ? $standardActions : $actions)
            ->toolbarActions([
                BulkActionGroup::make(
                    empty($bulkActions) ? $standardBulkActions : array_merge($bulkActions, $standardBulkActions)
                ),
            ])
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }
    
    /**
     * Get standard form sections
     */
    protected static function getStandardFormSections(): array
    {
        return [
            'basic_info' => static::getBasicInformationSection(),
            'contact_info' => static::getContactInformationSection(),
            'additional_info' => static::getAdditionalInformationSection(),
            'timestamps' => static::getTimestampsSection(),
        ];
    }
    
    /**
     * Get basic information section
     */
    protected static function getBasicInformationSection(array $schema = []): Section
    {
        $defaultSchema = [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            static::getActiveToggleField(),
        ];
        
        return Section::make('Basic Information')
            ->schema(array_merge($defaultSchema, $schema))
            ->columns(2);
    }
    
    /**
     * Get contact information section
     */
    protected static function getContactInformationSection(array $schema = []): Section
    {
        $defaultSchema = [
            TextInput::make('contact_name')
                ->label('Contact Name')
                ->maxLength(255),
            TextInput::make('contact_email')
                ->label('Contact Email')
                ->email()
                ->maxLength(255),
            TextInput::make('contact_phone')
                ->label('Contact Phone')
                ->tel()
                ->maxLength(255),
            Textarea::make('address')
                ->label('Address')
                ->rows(3)
                ->columnSpanFull(),
        ];
        
        return Section::make('Contact Information')
            ->schema(array_merge($defaultSchema, $schema))
            ->columns(2);
    }
    
    /**
     * Get additional information section
     */
    protected static function getAdditionalInformationSection(array $schema = []): Section
    {
        $defaultSchema = [
            static::getNotesField(),
        ];
        
        return Section::make('Additional Information')
            ->schema(array_merge($defaultSchema, $schema));
    }
    
    /**
     * Get timestamps section
     */
    protected static function getTimestampsSection(): Section
    {
        return Section::make('System Information')
            ->schema([
                Placeholder::make('created_at')
                    ->label('Created')
                    ->content(fn ($record): string => $record ? $record->created_at->format('M d, Y H:i') : 'Not created yet'),
                Placeholder::make('updated_at')
                    ->label('Last Updated')
                    ->content(fn ($record): string => $record ? $record->updated_at->format('M d, Y H:i') : 'Not updated yet'),
            ])
            ->columns(2)
            ->collapsed()
            ->hidden(fn ($record): bool => $record === null);
    }
    
    /**
     * Get standard table columns
     */
    protected static function getStandardTableColumns(): array
    {
        $columns = [];
        
        // Check if model uses HasActiveStatus trait
        if (method_exists(static::getModel(), 'scopeActive')) {
            $columns[] = static::getActiveBadgeColumn();
        }
        
        // Check if model has timestamps
        if (method_exists(static::getModel(), 'getCreatedAtColumn')) {
            $columns = array_merge($columns, static::getTimestampColumns());
        }
        
        return $columns;
    }
    
    /**
     * Get standard table filters
     */
    protected static function getStandardTableFilters(): array
    {
        $filters = [];
        
        // Check if model uses HasActiveStatus trait
        if (method_exists(static::getModel(), 'scopeActive')) {
            $filters[] = static::getActiveStatusFilter();
        }
        
        // Add date range filters if model has timestamps
        if (method_exists(static::getModel(), 'getCreatedAtColumn')) {
            $filters[] = static::getDateRangeFilter('created_at', 'Created Date');
        }
        
        return $filters;
    }
    
    /**
     * Get active status filter
     */
    protected static function getActiveStatusFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label('Active Status')
            ->boolean()
            ->trueLabel('Active only')
            ->falseLabel('Inactive only')
            ->placeholder('All');
    }
    
    /**
     * Get date range filter
     */
    protected static function getDateRangeFilter(string $field, string $label): Filter
    {
        return Filter::make($field)
            ->label($label)
            ->schema([
                DatePicker::make($field . '_from')
                    ->label('From'),
                DatePicker::make($field . '_until')
                    ->label('Until'),
            ])
            ->query(function (Builder $query, array $data) use ($field): Builder {
                return $query
                    ->when(
                        $data[$field . '_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate($field, '>=', $date),
                    )
                    ->when(
                        $data[$field . '_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate($field, '<=', $date),
                    );
            });
    }
    
    /**
     * Get select filter for relationships
     */
    protected static function getRelationshipFilter(string $relationship, string $label, string $titleAttribute = 'name'): SelectFilter
    {
        return SelectFilter::make($relationship . '_id')
            ->label($label)
            ->relationship($relationship, $titleAttribute)
            ->searchable()
            ->preload();
    }
    
    /**
     * Get standard table actions
     */
    protected static function getStandardTableActions(): array
    {
        return [
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
        ];
    }
    
    /**
     * Get standard bulk actions
     */
    protected static function getStandardBulkActions(): array
    {
        $actions = [
            DeleteBulkAction::make(),
        ];
        
        // Check if model uses HasActiveStatus trait
        if (method_exists(static::getModel(), 'scopeActive')) {
            $actions = array_merge($actions, static::getActiveStatusBulkActions());
        }
        
        return $actions;
    }
    
    /**
     * Get activate/deactivate bulk actions
     */
    protected static function getActiveStatusBulkActions(): array
    {
        return [
            BulkAction::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check-circle')
                ->action(function (Collection $records) {
                    $records->each->update(['is_active' => true]);
                })
                ->requiresConfirmation()
                ->color('success')
                ->deselectRecordsAfterCompletion(),
                
            BulkAction::make('deactivate')
                ->label('Deactivate')
                ->icon('heroicon-o-x-circle')
                ->action(function (Collection $records) {
                    $records->each->update(['is_active' => false]);
                })
                ->requiresConfirmation()
                ->color('danger')
                ->deselectRecordsAfterCompletion(),
        ];
    }
    
    /**
     * Get an active toggle field for forms
     */
    protected static function getActiveToggleField(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->default(true)
            ->helperText('Toggle to activate or deactivate this record')
            ->inline(false);
    }
    
    /**
     * Get a standard text column
     */
    protected static function getTextColumn(
        string $field,
        string $label,
        bool $searchable = true,
        bool $sortable = true,
        bool $toggleable = true
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->searchable($searchable)
            ->sortable($sortable)
            ->toggleable($toggleable);
    }

    /**
     * Get a standard name column (most common pattern)
     */
    protected static function getNameColumn(string $label = 'Name', bool $sortable = true, bool $searchable = true): TextColumn
    {
        return TextColumn::make('name')
            ->label($label)
            ->searchable($searchable)
            ->sortable($sortable)
            ->toggleable();
    }
    
    /**
     * Get a clickable name column that links to edit page
     */
    protected static function getClickableNameColumn(string $label = 'Name', string $color = 'primary'): TextColumn
    {
        return TextColumn::make('name')
            ->label($label)
            ->searchable()
            ->sortable()
            ->toggleable()
            ->url(fn ($record): string => static::getUrl('edit', ['record' => $record]))
            ->color($color);
    }

    /**
     * Get an active badge column
     */
    protected static function getActiveBadgeColumn(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->sortable()
            ->toggleable();
    }

    /**
     * Get standard timestamp columns
     */
    protected static function getTimestampColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get default table actions
     */
    protected static function getDefaultTableActions(): array
    {
        return static::getStandardTableActions();
    }

    /**
     * Get default bulk actions
     */
    protected static function getDefaultBulkActions(): array
    {
        return static::getStandardBulkActions();
    }

    /**
     * Get a status badge column with standard colors
     */
    protected static function getStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status',
        array $colorMap = []
    ): TextColumn {
        $defaultColorMap = [
            'active' => 'success',
            'inactive' => 'danger',
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            'draft' => 'gray',
            'in_progress' => 'info',
            'in_stock' => 'success',
            'out_of_stock' => 'danger',
            'reorder_needed' => 'warning',
        ];

        $colors = array_merge($defaultColorMap, $colorMap);

        return TextColumn::make($field)
            ->label($label)
            ->badge()
            ->color(fn (string $state): string => $colors[$state] ?? 'gray')
            ->toggleable();
    }

    /**
     * Get a price column formatted with currency
     */
    protected static function getPriceColumn(
        string $field = 'price',
        string $label = 'Price',
        string $currency = 'USD'
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->money($currency)
            ->sortable()
            ->toggleable();
    }

    /**
     * Get a relationship column
     */
    protected static function getRelationshipColumn(
        string $field,
        string $label,
        bool $searchable = true,
        bool $sortable = true
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->searchable($searchable)
            ->sortable($sortable)
            ->toggleable();
    }
    
    /**
     * Get a boolean badge column
     */
    protected static function getBooleanBadgeColumn(
        string $field,
        string $label,
        string $trueLabel = 'Yes',
        string $falseLabel = 'No',
        string $trueColor = 'success',
        string $falseColor = 'danger'
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->badge()
            ->formatStateUsing(fn (bool $state): string => $state ? $trueLabel : $falseLabel)
            ->color(fn (bool $state): string => $state ? $trueColor : $falseColor)
            ->sortable()
            ->toggleable();
    }
    
    /**
     * Get standard notes/description form field
     */
    protected static function getNotesField(string $field = 'notes', string $label = 'Notes'): Textarea
    {
        return Textarea::make($field)
            ->label($label)
            ->rows(3)
            ->columnSpanFull();
    }
    
    /**
     * Get description textarea field
     */
    protected static function getDescriptionField(string $field = 'description', string $label = 'Description'): Textarea
    {
        return Textarea::make($field)
            ->label($label)
            ->rows(3)
            ->maxLength(65535)
            ->columnSpanFull();
    }

    
    /**
     * Get a standard name field with optional custom label
     */
    protected static function getNameField(string $label = 'Name', bool $required = true, int $maxLength = 255): TextInput
    {
        return TextInput::make('name')
            ->label($label)
            ->required($required)
            ->maxLength($maxLength);
    }
    
    /**
     * Get a name field with uniqueness validation
     */
    protected static function getUniqueNameField(string $label = 'Name', bool $required = true, string $ignoreColumn = 'id'): TextInput
    {
        return TextInput::make('name')
            ->label($label)
            ->required($required)
            ->maxLength(255)
            ->unique(static::getModel(), 'name', ignoreRecord: true);
    }
    
    /**
     * Get supplier select field (commonly used across consumables)
     */
    protected static function getSupplierSelect(string $label = 'Supplier', bool $required = false): Select
    {
        return Select::make('supplier_id')
            ->label($label)
            ->relationship('supplier', 'name')
            ->searchable()
            ->preload()
            ->required($required)
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('contact_email')
                    ->email()
                    ->maxLength(255),
            ]);
    }
    
    /**
     * Get category select field
     */
    protected static function getCategorySelect(string $label = 'Category', bool $required = false): Select
    {
        return Select::make('category_id')
            ->label($label)
            ->relationship('category', 'name')
            ->searchable()
            ->preload()
            ->required($required);
    }
    
    /**
     * Get email field
     */
    protected static function getEmailField(string $field = 'email', string $label = 'Email', bool $required = false): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->email()
            ->maxLength(255)
            ->required($required);
    }
    
    /**
     * Get phone field
     */
    protected static function getPhoneField(string $field = 'phone', string $label = 'Phone', bool $required = false): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->tel()
            ->maxLength(255)
            ->required($required);
    }
    
    /**
     * Get URL field
     */
    protected static function getUrlField(string $field = 'website', string $label = 'Website', bool $required = false): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->url()
            ->maxLength(255)
            ->required($required);
    }
    
    /**
     * Get price field with currency formatting
     */
    protected static function getPriceField(string $field = 'price', string $label = 'Price', string $prefix = '$', bool $required = false): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()
            ->prefix($prefix)
            ->minValue(0)
            ->step(0.01)
            ->required($required);
    }
    
    /**
     * Get quantity field
     */
    protected static function getQuantityField(string $field = 'quantity', string $label = 'Quantity', bool $required = true, float $step = 1): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->step($step)
            ->required($required);
    }
    
    /**
     * Get a standard select field for relationships
     */
    protected static function getRelationshipSelect(
        string $relationship,
        string $titleAttribute = 'name',
        string $label = null,
        bool $required = false,
        bool $searchable = true
    ): Select {
        return Select::make($relationship . '_id')
            ->label($label ?? ucfirst(str_replace('_', ' ', $relationship)))
            ->relationship($relationship, $titleAttribute)
            ->searchable($searchable)
            ->preload()
            ->required($required);
    }
    
    /**
     * Get standard form schema with sections
     */
    protected static function getStandardFormSchema(array $additionalSections = []): array
    {
        $sections = static::getStandardFormSections();
        
        $schema = [];
        
        // Add basic info section if it exists
        if (isset($sections['basic_info'])) {
            $schema[] = $sections['basic_info'];
        }
        
        // Add any additional sections
        foreach ($additionalSections as $section) {
            $schema[] = $section;
        }
        
        // Add timestamps section if it exists
        if (isset($sections['timestamps'])) {
            $schema[] = $sections['timestamps'];
        }
        
        return $schema;
    }
    
    /**
     * Get truncated text column for long text fields
     */
    protected static function getTruncatedTextColumn(
        string $field,
        string $label,
        int $length = 50,
        bool $searchable = true
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->limit($length)
            ->tooltip(function (TextColumn $column) use ($length): ?string {
                $state = $column->getState();
                
                if (strlen($state) <= $length) {
                    return null;
                }
                
                return $state;
            })
            ->searchable($searchable)
            ->toggleable();
    }
    
    /**
     * Get standard searchable columns configuration
     */
    protected static function configureSearchableColumns(Table $table, array $searchableColumns): Table
    {
        return $table->searchable(
            fn () => $searchableColumns
        );
    }
    
    /**
     * Get a count column for relationships
     */
    protected static function getCountColumn(
        string $relationship,
        string $label = null,
        string $color = 'primary'
    ): TextColumn {
        $label = $label ?? ucfirst(str_replace('_', ' ', $relationship));
        
        return TextColumn::make($relationship . '_count')
            ->label($label)
            ->counts($relationship)
            ->sortable()
            ->color($color)
            ->toggleable();
    }
    
    /**
     * Get standard form with sections and custom content
     */
    protected static function getStandardForm(Schema $form, array $customSchema = []): Schema
    {
        $sections = static::getStandardFormSections();
        $schema = [];
        
        // Add basic info if exists
        if (isset($sections['basic_info'])) {
            $schema[] = $sections['basic_info'];
        }
        
        // Add custom schema
        foreach ($customSchema as $section) {
            $schema[] = $section;
        }
        
        // Add timestamps if exists
        if (isset($sections['timestamps'])) {
            $schema[] = $sections['timestamps'];
        }
        
        return $form->components($schema);
    }
    
    /**
     * Get numeric column with formatting
     */
    protected static function getNumericColumn(
        string $field,
        string $label,
        int $decimals = 0,
        bool $sortable = true
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->numeric($decimals)
            ->sortable($sortable)
            ->toggleable();
    }
    
    /**
     * Get date column
     */
    protected static function getDateColumn(
        string $field,
        string $label,
        string $format = null,
        bool $sortable = true
    ): TextColumn {
        $column = TextColumn::make($field)
            ->label($label)
            ->sortable($sortable)
            ->toggleable();
            
        if ($format) {
            $column->date($format);
        } else {
            $column->date();
        }
        
        return $column;
    }
    
    /**
     * Get datetime column
     */
    protected static function getDateTimeColumn(
        string $field,
        string $label,
        bool $sortable = true
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->dateTime()
            ->sortable($sortable)
            ->toggleable();
    }
    
    /**
     * Apply standard table configuration with grouping and header actions
     */
    protected static function applyStandardTableConfiguration(
        Table $table,
        array $groups = [],
        array $headerActions = [],
        string $defaultSort = 'created_at',
        string $defaultSortDirection = 'desc'
    ): Table {
        $table = $table->defaultSort($defaultSort, $defaultSortDirection);
        
        if (!empty($groups)) {
            $table = $table->groups($groups);
        }
        
        if (!empty($headerActions)) {
            $table = $table->headerActions($headerActions);
        }
        
        return $table;
    }
    
    /**
     * Get standard modal actions (save/cancel)
     */
    protected static function getModalActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->color('primary')
                ->submit(),
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->cancel(),
        ];
    }
    
    /**
     * Get badge column with state formatting
     */
    protected static function getBadgeColumn(
        string $field,
        string $label,
        array $formatStates = [],
        array $colors = []
    ): TextColumn {
        $column = TextColumn::make($field)
            ->label($label)
            ->badge()
            ->sortable()
            ->toggleable();
            
        if (!empty($formatStates)) {
            $column->formatStateUsing(function ($state) use ($formatStates) {
                return $formatStates[$state] ?? $state;
            });
        }
        
        if (!empty($colors)) {
            $column->color(function ($state) use ($colors) {
                return $colors[$state] ?? 'gray';
            });
        }
        
        return $column;
    }
}