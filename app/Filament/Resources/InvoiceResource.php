<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

/**
 * Billing and financial workflow interface for agricultural sales operations.
 * 
 * Manages comprehensive invoicing system for microgreens business including
 * individual order invoices and consolidated billing for wholesale customers.
 * Provides complete invoice lifecycle management from creation through payment
 * tracking with integrated PDF generation and customer communication.
 * 
 * @filament_resource
 * @business_domain Agricultural sales billing and accounts receivable management
 * @workflow_support Invoice creation, status tracking, payment processing, collections
 * @related_models Invoice, Order, Customer, User, Payment
 * @ui_features Status transitions, bulk operations, PDF downloads, payment tracking
 * @financial_operations Individual and consolidated invoicing for different customer types
 * @integration Order fulfillment, payment processing, customer communication
 * 
 * Invoice Types and Workflows:
 * - Individual Order Invoices: Standard invoices for single order fulfillment
 * - Consolidated Invoices: Monthly/periodic billing combining multiple orders
 * - Wholesale Invoices: Volume-based pricing with extended payment terms
 * - Retail Invoices: Individual customer transactions with immediate payment
 * 
 * Agricultural Business Features:
 * - Seasonal billing cycles aligned with growing seasons and harvest schedules  
 * - Customer type-specific invoice templates (retail, wholesale, institutional)
 * - Product perishability considerations in payment terms and collection timing
 * - Integration with harvest schedules for accurate invoice timing
 * - Quality guarantee provisions and credit memo handling
 * 
 * Financial Operations:
 * - Automated invoice generation from completed orders
 * - Progressive status tracking (draft, sent, paid, overdue, cancelled)
 * - Payment term management based on customer relationships
 * - Overdue invoice identification and collections workflow
 * - Revenue recognition and financial reporting integration
 * - Tax calculation and compliance for agricultural products
 * 
 * Business Process Integration:
 * - Order completion triggers invoice creation workflow
 * - Harvest scheduling coordinates invoice timing for freshness
 * - Customer payment history influences credit terms and limits
 * - Seasonal demand patterns inform billing cycle optimization
 * - Quality issue resolution integrated with credit memo processing
 * 
 * @architecture Comprehensive invoice management with agricultural business context
 * @compliance Financial record-keeping and agricultural sales tax requirements
 */
class InvoiceResource extends BaseResource
{
    /** @var string The Eloquent model class for agricultural invoice management */
    protected static ?string $model = Invoice::class;

    /** @var string Navigation icon representing document/invoice concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    
    /** @var string Navigation group for sales and order management */
    protected static string | \UnitEnum | null $navigationGroup = 'Orders & Sales';
    
    /** @var int Secondary navigation position after orders */
    protected static ?int $navigationSort = 2;
    
    /** @var string Record identifier for page titles and references */
    protected static ?string $recordTitleAttribute = 'invoice_number';

    /**
     * Build the Filament form schema for agricultural invoice management.
     * 
     * Creates comprehensive invoice creation and editing interface with order
     * selection, amount calculation, status management, and payment tracking.
     * Form adapts dynamically based on invoice type (individual vs consolidated)
     * and provides conditional field visibility for payment workflow states.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with invoice management and payment tracking
     * @order_integration Links to wholesale orders with availability validation
     * @status_workflow Conditional fields based on invoice status progression
     * @payment_tracking Date fields for payment and communication milestones
     * @business_logic Automatic invoice numbering and default payment terms
     * @agricultural_context 30-day standard terms accommodate seasonal cash flow
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Details')
                    ->schema([
                        Select::make('order_id')
                            ->relationship('order', 'id', function (Builder $query) {
                                return $query->whereNotIn('id', function ($query) {
                                    $query->select('order_id')
                                        ->from('invoices');
                                })->where('customer_type', 'wholesale');
                            })
                            ->preload()
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                DatePicker::make('harvest_date')
                                    ->required(),
                                DatePicker::make('delivery_date')
                                    ->required(),
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Select::make('customer_type')
                                    ->options([
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale',
                                    ])
                                    ->default('wholesale')
                                    ->required(),
                            ]),
                            
                        TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'INV-' . str_pad(random_int(1, 99999), 5, 'unit', STR_PAD_LEFT)),
                            
                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0)
                            ->step(0.01),
                            
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                            
                        DateTimePicker::make('sent_at')
                            ->label('Sent At')
                            ->visible(fn (Get $get) => in_array($get('status'), ['sent', 'paid', 'overdue'])),
                            
                        DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->visible(fn (Get $get) => $get('status') === 'paid'),
                            
                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(fn () => now()->addDays(30))
                            ->required(),
                            
                        Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Build the Filament data table for invoice management and payment tracking.
     * 
     * Creates comprehensive invoice overview with status indicators, customer
     * information, payment tracking, and action workflows. Table provides
     * efficient invoice management with status-based actions, bulk operations,
     * and integrated PDF generation for customer communication.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with invoice management and financial tracking
     * @performance Eager loads relationships for customer and order information
     * @status_indicators Visual badges and icons for quick invoice status assessment
     * @action_workflows Context-sensitive actions based on invoice status progression
     * @bulk_operations Mass status updates for efficient invoice processing
     * @financial_tracking Amount display, due date monitoring, payment milestone tracking
     * @persistence Session-persistent filters and searches for workflow continuity
     * @pdf_integration Direct invoice download access for customer communication
     * @agricultural_context Consolidated invoice indicators for wholesale operations
     */
    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'user',
                'order.user',
                'order.customer'
            ]))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('order.id')
                    ->label('Order ID')
                    ->searchable()
                    ->placeholder('Consolidated')
                    ->sortable(),
                    
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->getStateUsing(fn (Invoice $record) => $record->user->name ?? $record->order?->user?->name ?? 'Unknown')
                    ->sortable(),
                    
                IconColumn::make('is_consolidated')
                    ->label('Type')
                    ->boolean()
                    ->trueIcon('heroicon-o-squares-2x2')
                    ->falseIcon('heroicon-o-document-text')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->tooltip(fn (Invoice $record) => $record->is_consolidated ? 'Consolidated Invoice' : 'Regular Invoice'),
                    
                TextColumn::make('consolidated_order_count')
                    ->label('Orders')
                    ->placeholder('1')
                    ->toggleable()
                    ->tooltip('Number of orders in this consolidated invoice'),
                    
                TextColumn::make('effective_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->getStateUsing(fn (Invoice $record) => $record->total_amount ?? $record->amount)
                    ->sortable(),
                    
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'primary' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ]),
                    
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Due Date'),
                    
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('due_date')
                    ->schema([
                        DatePicker::make('due_from'),
                        DatePicker::make('due_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View invoice'),
                    EditAction::make()
                        ->tooltip('Edit invoice'),
                    DeleteAction::make()
                        ->tooltip('Delete invoice'),
                    Action::make('Mark as Sent')
                        ->tooltip('Mark invoice as sent')
                        ->action(fn (Invoice $record) => $record->markAsSent())
                        ->requiresConfirmation()
                        ->color('primary')
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(fn (Invoice $record) => $record->status === 'draft'),
                    Action::make('Mark as Paid')
                        ->action(fn (Invoice $record) => $record->markAsPaid())
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (Invoice $record) => in_array($record->status, ['sent', 'overdue'])),
                    Action::make('Mark as Overdue')
                        ->action(fn (Invoice $record) => $record->markAsOverdue())
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn (Invoice $record) => $record->status === 'sent' && $record->due_date < now()),
                    Action::make('Cancel Invoice')
                        ->action(fn (Invoice $record) => $record->markAsCancelled())
                        ->requiresConfirmation()
                        ->color('gray')
                        ->icon('heroicon-o-x-mark')
                        ->visible(fn (Invoice $record) => in_array($record->status, ['draft', 'sent', 'overdue'])),
                    Action::make('Download PDF')
                        ->url(fn (Invoice $record): string => route('invoices.download', $record))
                        ->openUrlInNewTab()
                        ->color('info')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(fn (Invoice $record) => in_array($record->status, ['sent', 'paid', 'overdue', 'pending'])),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('mark_sent')
                        ->label('Mark as Sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn (Collection $records) => $records->each->markAsSent())
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->markAsPaid())
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    /**
     * Define relationship managers for invoice resource.
     * 
     * No relationship managers configured as invoice relationships are managed
     * through their respective resources and the invoice workflow focuses on
     * status management and payment tracking rather than relationship editing.
     * 
     * @return array<class-string> Empty array - relationships managed in dedicated resources
     * @workflow_focus Invoice management concentrates on payment processing workflow
     * @design_pattern Invoice relationships viewed through dedicated order/payment resources
     * @performance Avoids complex relationship loading on invoice management pages
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Define the page routes and classes for invoice resource.
     * 
     * Provides complete CRUD workflow for invoice management including detailed
     * view page for payment tracking and status history. Comprehensive page
     * access supports financial audit requirements and payment dispute resolution
     * with full invoice lifecycle documentation.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes Complete CRUD workflow for comprehensive invoice management
     * @view_page Detailed invoice view supports payment tracking and status history
     * @financial_audit Complete page access supports accounting and compliance requirements
     * @dispute_resolution Detailed invoice information for payment issue investigation
     */
    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
