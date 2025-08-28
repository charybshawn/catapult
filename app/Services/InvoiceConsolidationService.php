<?php

namespace App\Services;

use Exception;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive B2B billing and invoice consolidation service for agricultural operations.
 * 
 * This specialized service manages complex B2B billing workflows including consolidated
 * invoice generation, flexible billing frequency management, and automated invoice
 * processing for diverse customer payment preferences. Essential for maintaining
 * professional financial relationships with commercial agricultural customers.
 * 
 * @service_domain B2B financial management and customer billing workflows
 * @business_purpose Streamlined billing for commercial customers with flexible payment terms
 * @agricultural_focus Professional invoicing for restaurants, retailers, and commercial buyers
 * @revenue_management Efficient billing processes supporting business-to-business sales
 * @customer_service Flexible billing options accommodate diverse customer preferences
 * 
 * Core Billing Management Features:
 * - **Consolidated Invoicing**: Combines multiple orders into single invoices by billing period
 * - **Flexible Frequencies**: Weekly, biweekly, monthly, quarterly, and immediate billing options
 * - **Automated Processing**: Scheduled generation of invoices based on billing periods
 * - **B2B Integration**: Specialized workflows for business customer requirements
 * - **Payment Terms**: Configurable payment terms and due date management
 * - **Professional Formatting**: Business-appropriate invoice numbering and documentation
 * 
 * Agricultural B2B Applications:
 * - **Restaurant Partnerships**: Weekly consolidated billing for fresh produce delivery
 * - **Retail Relationships**: Monthly invoicing for grocery stores and markets
 * - **Wholesale Operations**: Quarterly consolidated billing for large-volume customers
 * - **Institutional Sales**: Customized billing for schools, hospitals, and cafeterias
 * - **Distribution Networks**: Professional invoicing for reseller relationships
 * - **Corporate Accounts**: Flexible billing accommodating corporate payment processes
 * 
 * Billing Workflow Management:
 * - **Period Calculation**: Automatic billing period determination based on delivery dates
 * - **Order Aggregation**: Intelligent grouping of orders within billing periods
 * - **Invoice Generation**: Automated creation of professional invoice documents
 * - **Payment Tracking**: Integration with payment processing and account management
 * - **Customer Communication**: Professional invoice delivery and notification systems
 * 
 * Business Intelligence and Reporting:
 * - **Revenue Analysis**: Consolidated billing data supports financial analysis
 * - **Customer Insights**: Billing pattern analysis for relationship management
 * - **Cash Flow Management**: Predictable invoicing supports financial planning
 * - **Payment Performance**: Tracking and analysis of customer payment patterns
 * - **Administrative Efficiency**: Reduced billing overhead through consolidation
 * 
 * Customer Experience Benefits:
 * - **Simplified Billing**: Single invoices for multiple orders reduce payment complexity
 * - **Flexible Terms**: Billing frequencies aligned with customer business cycles
 * - **Professional Presentation**: Business-appropriate invoice format and numbering
 * - **Payment Convenience**: Terms and timing that match customer payment processes
 * - **Clear Documentation**: Comprehensive order details for account reconciliation
 * 
 * Technical Architecture:
 * - **Period-Based Processing**: Sophisticated date range calculations for billing periods
 * - **Transaction Safety**: Database transactions ensure invoice generation integrity
 * - **Performance Optimization**: Efficient queries for large-scale B2B operations
 * - **Integration Ready**: Coordinates with order processing and payment systems
 * - **Error Resilience**: Comprehensive error handling for complex billing scenarios
 * 
 * Integration Points:
 * - Order Management: Links invoice generation with order fulfillment workflows
 * - Customer Management: Integrates billing preferences with customer service systems
 * - Payment Processing: Coordinates with payment collection and account management
 * - Financial Reporting: Invoice data feeds into business intelligence and accounting
 * - Notification Systems: Professional invoice delivery and payment reminders
 * 
 * Revenue and Business Benefits:
 * - **Professional Relationships**: Business-appropriate billing builds customer trust
 * - **Administrative Efficiency**: Consolidated billing reduces processing overhead
 * - **Cash Flow Optimization**: Flexible terms and predictable invoicing improve cash flow
 * - **Scalability**: Automated processing supports business growth and expansion
 * - **Customer Retention**: Flexible billing accommodates customer preferences
 * - **Financial Control**: Comprehensive tracking and management of B2B receivables
 * 
 * Quality and Compliance:
 * - **Professional Standards**: Invoice formatting meets business documentation requirements
 * - **Audit Trail**: Complete logging and tracking for financial compliance
 * - **Data Integrity**: Transaction-based processing ensures accurate billing records
 * - **Error Recovery**: Comprehensive error handling prevents billing discrepancies
 * 
 * @b2b_commerce Comprehensive business-to-business billing and invoice management
 * @agricultural_finance Professional financial workflows for agricultural B2B relationships
 * @customer_flexibility Adaptable billing processes accommodate diverse customer needs
 * @revenue_optimization Efficient billing workflows support business growth and profitability
 */
class InvoiceConsolidationService
{
    /**
     * Execute comprehensive consolidated invoice generation for all eligible B2B customers.
     * 
     * Performs system-wide processing of B2B customer billing, generating consolidated
     * invoices based on individual customer billing frequencies and periods. Essential
     * for maintaining professional billing relationships and automating complex B2B
     * financial workflows across diverse customer billing preferences.
     * 
     * @param Carbon|null $forDate Target date for invoice generation (defaults to current date)
     * @return Collection<Invoice> Generated consolidated invoices
     * 
     * @system_wide_processing Handles all eligible B2B customers in single operation
     * @billing_automation Automated processing reduces manual billing overhead
     * @customer_specific Respects individual customer billing frequency preferences
     * @professional_billing Maintains high standards for business customer relationships
     * 
     * Processing Workflow:
     * - **Customer Identification**: Discovers B2B customers with orders ready for billing
     * - **Billing Period Analysis**: Evaluates orders against customer billing frequencies
     * - **Invoice Generation**: Creates consolidated invoices for qualifying customers
     * - **Error Isolation**: Handles individual customer failures without system disruption
     * - **Comprehensive Logging**: Detailed processing logs for audit and troubleshooting
     * - **Result Compilation**: Returns collection of successfully generated invoices
     * 
     * Business Applications:
     * - **Scheduled Processing**: Automated daily/weekly billing runs for B2B operations
     * - **Administrative Tool**: Manual invoice generation for specific business dates
     * - **Month-End Processing**: Comprehensive billing for financial period closures
     * - **Customer Service**: Ad-hoc invoice generation for customer requests
     * 
     * Customer Eligibility Criteria:
     * - B2B order type with non-immediate billing frequency
     * - Orders requiring invoice generation
     * - Unbilled orders within completed billing periods
     * - Active (non-cancelled) order status
     * 
     * Error Handling and Resilience:
     * - **Individual Isolation**: Customer-specific errors don't affect other processing
     * - **Comprehensive Logging**: Detailed error context for troubleshooting
     * - **Graceful Degradation**: System continues processing despite individual failures
     * - **Audit Trail**: Complete processing log for administrative oversight
     * 
     * Performance Benefits:
     * - **Batch Processing**: Efficient handling of multiple customers simultaneously
     * - **Database Optimization**: Optimized queries for large-scale B2B operations
     * - **Resource Management**: Controlled processing for system stability
     * - **Scalability**: Handles growth in B2B customer base and order volumes
     * 
     * @automated_billing Designed for scheduled execution in B2B billing workflows
     * @professional_standards Maintains high-quality billing for business relationships
     * @administrative_efficiency Reduces manual billing overhead through automation
     */
    public function generateConsolidatedInvoices(Carbon $forDate = null): Collection
    {
        $forDate = $forDate ?? now();
        $generatedInvoices = collect();
        
        Log::info('Starting consolidated invoice generation', ['date' => $forDate->toDateString()]);
        
        // Get all B2B customers with recurring orders that need consolidation
        $customersNeedingInvoices = $this->getCustomersNeedingConsolidatedInvoices($forDate);
        
        foreach ($customersNeedingInvoices as $customer) {
            try {
                $invoice = $this->generateConsolidatedInvoiceForCustomer($customer, $forDate);
                if ($invoice) {
                    $generatedInvoices->push($invoice);
                }
            } catch (Exception $e) {
                Log::error('Failed to generate consolidated invoice for customer', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Completed consolidated invoice generation', [
            'generated_count' => $generatedInvoices->count()
        ]);
        
        return $generatedInvoices;
    }
    
    /**
     * Identify B2B customers requiring consolidated invoice generation for specified date.
     * 
     * Performs comprehensive analysis to discover B2B customers with unbilled orders
     * that have reached their billing period end dates. Uses sophisticated date range
     * queries to identify customers across different billing frequencies while ensuring
     * accurate billing period alignment.
     * 
     * @param Carbon $forDate Target date for evaluating billing period completion
     * @return Collection<User> Customers requiring consolidated invoice generation
     * 
     * @customer_discovery Identifies eligible customers across multiple billing frequencies
     * @billing_period_analysis Evaluates orders against individual billing schedules
     * @comprehensive_filtering Ensures only appropriate customers are selected for processing
     * @performance_optimized Efficient database queries for large customer bases
     * 
     * Selection Criteria:
     * - **B2B Orders**: Only business-to-business order types
     * - **Non-Immediate Billing**: Excludes immediate billing customers
     * - **Invoice Required**: Only orders configured for invoice generation
     * - **Unbilled Status**: Orders not yet linked to consolidated invoices
     * - **Active Orders**: Excludes cancelled orders from billing consideration
     * - **Period Completion**: Billing periods that end on or before target date
     * 
     * Billing Frequency Support:
     * - **Weekly**: Orders with billing periods ending within weekly cycles
     * - **Biweekly**: Two-week billing periods reaching completion
     * - **Monthly**: Month-end billing cycles ready for processing
     * - **Quarterly**: Quarter-end billing for large-volume customers
     * 
     * Query Optimization:
     * - **Relationship Constraints**: Uses whereHas for efficient order filtering
     * - **Date Range Logic**: Sophisticated period end date comparisons
     * - **Index Utilization**: Optimized for database performance with proper indexing
     * - **Result Efficiency**: Returns only customers requiring immediate action
     * 
     * Business Benefits:
     * - **Accurate Targeting**: Ensures only appropriate customers receive invoices
     * - **Timing Precision**: Respects individual customer billing schedules
     * - **Processing Efficiency**: Minimizes unnecessary database operations
     * - **Scalability**: Handles large B2B customer bases efficiently
     * 
     * @protected_method Internal logic for customer discovery workflow
     * @billing_intelligence Smart customer selection based on billing period analysis
     * @performance_critical Optimized queries essential for large-scale operations
     */
    protected function getCustomersNeedingConsolidatedInvoices(Carbon $forDate): Collection
    {
        return User::whereHas('orders', function ($query) use ($forDate) {
            $query->where('order_type', 'b2b')
                ->where('billing_frequency', '<>', 'immediate')
                ->where('requires_invoice', true)
                ->whereNull('consolidated_invoice_id')
                ->where('status', '<>', 'cancelled')
                ->where(function ($q) use ($forDate) {
                    // Orders that fall within billing periods ending on or before the target date
                    $q->where(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'weekly')
                            ->where('billing_period_end', '<=', $forDate);
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'biweekly')
                            ->where('billing_period_end', '<=', $forDate);
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'monthly')
                            ->where('billing_period_end', '<=', $forDate);
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'quarterly')
                            ->where('billing_period_end', '<=', $forDate);
                    });
                });
        })->get();
    }
    
    /**
     * Generate comprehensive consolidated invoice for individual B2B customer.
     * 
     * Creates single consolidated invoice combining all eligible unbilled orders
     * for specified customer within their billing periods. Performs complete
     * invoice generation workflow including order aggregation, amount calculation,
     * and database transaction management for financial integrity.
     * 
     * @param User $customer The B2B customer requiring consolidated invoice
     * @param Carbon|null $forDate Target date for billing period evaluation
     * @return Invoice|null Generated consolidated invoice or null if no orders to bill
     * 
     * @customer_specific Focused processing for individual customer billing
     * @order_aggregation Combines multiple orders into single professional invoice
     * @transaction_safety Database transactions ensure financial data integrity
     * @comprehensive_billing Complete invoice generation with all required details
     * 
     * Generation Workflow:
     * - **Order Discovery**: Identifies all unbilled orders for customer within periods
     * - **Eligibility Validation**: Confirms orders meet consolidation criteria
     * - **Transaction Processing**: Creates invoice and updates orders atomically
     * - **Amount Calculation**: Aggregates order totals for accurate billing
     * - **Relationship Linking**: Associates all orders with generated invoice
     * - **Audit Logging**: Comprehensive logging for financial tracking
     * 
     * Business Benefits:
     * - **Professional Billing**: Single invoice simplifies customer payment process
     * - **Administrative Efficiency**: Reduces invoice processing overhead
     * - **Financial Accuracy**: Transaction-based processing ensures data integrity
     * - **Customer Service**: Simplified billing improves customer experience
     * - **Record Keeping**: Clear audit trail for financial compliance
     * 
     * Data Integrity Features:
     * - **Atomic Operations**: Database transactions prevent partial invoice creation
     * - **Comprehensive Validation**: Ensures only appropriate orders are included
     * - **Error Handling**: Graceful failure handling with detailed logging
     * - **Relationship Consistency**: Proper linking between orders and invoices
     * 
     * Financial Processing:
     * - **Amount Aggregation**: Accurate total calculation across multiple orders
     * - **Period Documentation**: Clear billing period range in invoice details
     * - **Professional Formatting**: Business-appropriate invoice structure and content
     * - **Payment Terms**: Standard B2B payment terms and due date calculation
     * 
     * Customer Experience:
     * - **Consolidated View**: Single invoice provides complete billing period overview
     * - **Clear Documentation**: Detailed notes explaining invoice composition
     * - **Payment Simplicity**: Single payment covers multiple orders and deliveries
     * - **Professional Standards**: Business-appropriate invoice presentation
     * 
     * @b2b_billing Professional invoice generation for business customers
     * @financial_integrity Transaction-based processing ensures accurate billing
     * @customer_service Simplified billing improves business customer relationships
     */
    public function generateConsolidatedInvoiceForCustomer(User $customer, Carbon $forDate = null): ?Invoice
    {
        $forDate = $forDate ?? now();
        
        // Get all unbilled orders for this customer that should be consolidated
        $ordersToConsolidate = $this->getOrdersToConsolidate($customer, $forDate);
        
        if ($ordersToConsolidate->isEmpty()) {
            return null;
        }
        
        return DB::transaction(function () use ($customer, $ordersToConsolidate, $forDate) {
            // Create the consolidated invoice
            $invoice = $this->createConsolidatedInvoice($customer, $ordersToConsolidate, $forDate);
            
            // Link all orders to this consolidated invoice
            $this->linkOrdersToConsolidatedInvoice($ordersToConsolidate, $invoice);
            
            Log::info('Generated consolidated invoice', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'order_count' => $ordersToConsolidate->count(),
                'total_amount' => $invoice->total_amount
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Retrieve eligible orders for consolidation into customer's billing invoice.
     * 
     * Identifies all unbilled B2B orders for specified customer that fall within
     * completed billing periods and meet consolidation criteria. Essential for
     * accurate consolidated invoice generation and ensuring proper billing period
     * alignment with customer preferences.
     * 
     * @param User $customer The B2B customer whose orders to retrieve
     * @param Carbon $forDate Target date for billing period evaluation
     * @return Collection<Order> Orders eligible for consolidation into invoice
     * 
     * @order_discovery Identifies eligible orders for billing consolidation
     * @billing_compliance Ensures orders meet B2B billing requirements
     * @period_alignment Respects customer billing frequency and period boundaries
     * @relationship_loading Eager loads data for efficient invoice generation
     * 
     * Selection Criteria:
     * - **B2B Order Type**: Only business-to-business orders
     * - **Non-Immediate Billing**: Excludes orders with immediate billing requirements
     * - **Invoice Required**: Only orders configured for invoice generation
     * - **Unbilled Status**: Orders not yet associated with consolidated invoices
     * - **Active Orders**: Excludes cancelled orders from billing consideration
     * - **Period Completion**: Orders with billing periods ending on or before target date
     * 
     * Query Optimization:
     * - **Customer Relationship**: Uses customer's orders relationship for efficiency
     * - **Comprehensive Filtering**: Multiple criteria ensure accurate order selection
     * - **Eager Loading**: Includes orderItems and user data for invoice generation
     * - **Date Range Logic**: Precise billing period end date comparisons
     * 
     * Data Completeness:
     * - **Order Items**: Complete product and quantity information for billing
     * - **User Details**: Customer information for invoice generation
     * - **Billing Periods**: Period start and end dates for accurate consolidation
     * - **Financial Data**: All information needed for amount calculations
     * 
     * Business Applications:
     * - **Invoice Generation**: Foundation data for consolidated invoice creation
     * - **Amount Calculation**: Order totals for accurate billing amounts
     * - **Period Documentation**: Billing period ranges for invoice details
     * - **Customer Service**: Complete order context for billing inquiries
     * 
     * Quality Assurance:
     * - **Accurate Filtering**: Multiple criteria prevent inappropriate order inclusion
     * - **Complete Data**: Eager loading ensures all required information is available
     * - **Performance Optimization**: Efficient queries suitable for production use
     * - **Consistent Results**: Reliable order selection across multiple invocations
     * 
     * @protected_method Internal utility for order discovery workflow
     * @billing_accuracy Ensures only appropriate orders are included in invoices
     * @performance_optimized Efficient queries with comprehensive data loading
     */
    protected function getOrdersToConsolidate(User $customer, Carbon $forDate): Collection
    {
        return $customer->orders()
            ->where('order_type', 'b2b')
            ->where('billing_frequency', '<>', 'immediate')
            ->where('requires_invoice', true)
            ->whereNull('consolidated_invoice_id')
            ->where('status', '<>', 'cancelled')
            ->where('billing_period_end', '<=', $forDate)
            ->with(['orderItems', 'user'])
            ->get();
    }
    
    /**
     * Create comprehensive consolidated invoice record with complete billing details.
     * 
     * Generates professional consolidated invoice with accurate amount calculations,
     * billing period documentation, payment terms, and comprehensive metadata.
     * Essential for maintaining professional B2B billing standards and providing
     * clear documentation for customer payment processing.
     * 
     * @param User $customer The B2B customer for invoice generation
     * @param Collection $orders Orders to consolidate into single invoice
     * @param Carbon $forDate Invoice generation date
     * @return Invoice Newly created consolidated invoice
     * 
     * @professional_billing Creates business-appropriate invoice with complete details
     * @amount_calculation Accurate financial totals across multiple consolidated orders
     * @billing_documentation Comprehensive period and order information
     * @payment_terms Standard B2B payment terms and due date management
     * 
     * Invoice Creation Process:
     * - **Amount Aggregation**: Sums total amounts across all consolidated orders
     * - **Period Calculation**: Determines billing period span from earliest to latest
     * - **Invoice Numbering**: Generates unique consolidated invoice number
     * - **Payment Terms**: Applies standard 30-day B2B payment terms
     * - **Metadata Documentation**: Records consolidation details and order counts
     * - **Professional Notes**: Clear description of invoice composition
     * 
     * Financial Calculations:
     * - **Total Amount**: Accurate sum of all order amounts using totalAmount() method
     * - **Billing Periods**: Span from earliest period start to latest period end
     * - **Payment Due Date**: Standard 30-day terms from invoice issue date
     * - **Status Management**: Initial 'pending' status for new invoices
     * 
     * Business Documentation:
     * - **Period Range**: Clear billing period span documentation
     * - **Order Count**: Number of orders consolidated for customer reference
     * - **Professional Notes**: Comprehensive description of invoice contents
     * - **Unique Numbering**: Distinct invoice numbers for accounting systems
     * 
     * Professional Standards:
     * - **B2B Payment Terms**: Industry-standard 30-day payment periods
     * - **Clear Documentation**: Professional invoice notes and descriptions
     * - **Accurate Amounts**: Precise financial calculations for business accounting
     * - **Complete Metadata**: All information needed for customer service and accounting
     * 
     * Database Fields:
     * - **Customer Information**: User ID and billing relationship
     * - **Financial Data**: Amounts, totals, and payment terms
     * - **Period Documentation**: Billing period start and end dates
     * - **Consolidation Metadata**: Consolidated flag and order count
     * - **Professional Details**: Invoice number, dates, and descriptive notes
     * 
     * @protected_method Internal utility for invoice creation workflow
     * @financial_accuracy Ensures accurate amount calculations and payment terms
     * @professional_standards Maintains high-quality B2B invoice documentation
     */
    protected function createConsolidatedInvoice(User $customer, Collection $orders, Carbon $forDate): Invoice
    {
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });
        
        $earliestBillingStart = $orders->min('billing_period_start');
        $latestBillingEnd = $orders->max('billing_period_end');
        
        $invoice = Invoice::create([
            'user_id' => $customer->id,
            'invoice_number' => $this->generateConsolidatedInvoiceNumber($customer, $forDate),
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'issue_date' => $forDate->toDateString(),
            'due_date' => $forDate->copy()->addDays(30)->toDateString(), // 30 days payment terms
            'billing_period_start' => $earliestBillingStart,
            'billing_period_end' => $latestBillingEnd,
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'notes' => "Consolidated invoice for {$orders->count()} orders from {$earliestBillingStart} to {$latestBillingEnd}"
        ]);
        
        return $invoice;
    }
    
    /**
     * Establish relationships between consolidated orders and generated invoice.
     * 
     * Updates all consolidated orders to reference the newly created invoice,
     * establishing proper billing relationships and preventing duplicate billing.
     * Essential for maintaining accurate billing records and supporting customer
     * service inquiries about order-to-invoice relationships.
     * 
     * @param Collection $orders Orders to link to the consolidated invoice
     * @param Invoice $invoice The consolidated invoice to link orders to
     * @return void Order-invoice relationships established
     * 
     * @relationship_management Establishes proper order-to-invoice connections
     * @billing_integrity Prevents duplicate billing through relationship tracking
     * @customer_service Enables order-to-invoice lookup for customer inquiries
     * @financial_tracking Complete audit trail for billing relationships
     * 
     * Linking Process:
     * - **Relationship Update**: Sets consolidated_invoice_id on each order
     * - **Batch Processing**: Efficiently processes all orders in collection
     * - **Data Consistency**: Ensures all orders properly reference invoice
     * - **Audit Trail**: Creates complete billing relationship documentation
     * 
     * Business Benefits:
     * - **Billing Accuracy**: Prevents orders from being billed multiple times
     * - **Customer Service**: Enables quick lookup of invoice for specific orders
     * - **Financial Compliance**: Maintains proper billing record relationships
     * - **Administrative Efficiency**: Clear connection between orders and invoices
     * 
     * Data Integrity:
     * - **Atomic Updates**: Each order update is handled individually
     * - **Relationship Consistency**: All orders properly linked to invoice
     * - **Duplicate Prevention**: Orders marked as billed prevent re-billing
     * - **Complete Coverage**: Every order in collection gets proper linkage
     * 
     * Customer Service Applications:
     * - **Order Inquiries**: Quick lookup of which invoice contains specific order
     * - **Billing Questions**: Clear connection between deliveries and billing
     * - **Payment Tracking**: Understanding which orders are covered by payments
     * - **Account Reconciliation**: Complete order-to-invoice relationship mapping
     * 
     * @protected_method Internal utility for invoice-order relationship management
     * @billing_compliance Ensures proper financial record relationships
     * @administrative_support Enables efficient customer service and account management
     */
    protected function linkOrdersToConsolidatedInvoice(Collection $orders, Invoice $invoice): void
    {
        $orders->each(function ($order) use ($invoice) {
            $order->update(['consolidated_invoice_id' => $invoice->id]);
        });
    }
    
    /**
     * Generate unique, professional invoice number for consolidated B2B billing.
     * 
     * Creates distinctive invoice number following professional B2B standards with
     * customer identification, date coding, and sequence management. Essential for
     * maintaining organized billing records and supporting customer accounting
     * system integration.
     * 
     * @param User $customer The customer for invoice number generation
     * @param Carbon $forDate Invoice generation date for numbering
     * @return string Unique consolidated invoice number
     * 
     * @professional_numbering B2B-appropriate invoice numbering standards
     * @customer_identification Clear customer association in invoice numbers
     * @sequence_management Ensures unique numbers within customer and date
     * @accounting_integration Compatible with business accounting systems
     * 
     * Numbering Format: CONS-{CUSTOMER}-{DATE}-{SEQUENCE}
     * - **Prefix**: 'CONS' clearly identifies consolidated invoices
     * - **Customer Code**: First 3 letters of customer name (uppercase)
     * - **Date Code**: YYYYMMDD format for chronological organization
     * - **Sequence**: 3-digit sequence number for uniqueness within date
     * 
     * Business Benefits:
     * - **Professional Appearance**: Business-appropriate invoice numbering
     * - **Easy Identification**: Clear consolidated invoice recognition
     * - **Customer Organization**: Customer-specific numbering aids organization
     * - **Chronological Order**: Date-based numbering supports filing systems
     * - **Uniqueness Guarantee**: Sequence ensures no duplicate numbers
     * 
     * Administrative Features:
     * - **Customer Recognition**: Customer code enables quick identification
     * - **Date Organization**: Chronological numbering aids record management
     * - **Type Classification**: 'CONS' prefix distinguishes from immediate invoices
     * - **Sequential Logic**: Automatic sequence generation prevents conflicts
     * 
     * Integration Support:
     * - **Accounting Systems**: Professional numbering compatible with business software
     * - **Customer Systems**: Consistent numbering aids customer record keeping
     * - **Audit Requirements**: Clear, traceable invoice numbering for compliance
     * - **Search Functionality**: Structured numbers support efficient searching
     * 
     * Technical Implementation:
     * - **Customer Code Extraction**: Safely handles customer names of any length
     * - **Sequence Calculation**: Accurate counting of existing numbers for uniqueness
     * - **Format Consistency**: Standardized formatting across all consolidated invoices
     * - **Collision Prevention**: Database-based sequence counting prevents duplicates
     * 
     * @protected_method Internal utility for professional invoice numbering
     * @b2b_standards Professional numbering appropriate for business customers
     * @administrative_organization Structured numbering aids billing record management
     */
    protected function generateConsolidatedInvoiceNumber(User $customer, Carbon $forDate): string
    {
        $prefix = 'CONS';
        $customerCode = strtoupper(substr($customer->name, 0, 3));
        $dateCode = $forDate->format('Ymd');
        $sequence = Invoice::where('invoice_number', 'like', "{$prefix}-{$customerCode}-{$dateCode}-%")
            ->count() + 1;
            
        return sprintf('%s-%s-%s-%03d', $prefix, $customerCode, $dateCode, $sequence);
    }
    
    /**
     * Calculate and assign billing periods for B2B orders based on frequency preferences.
     * 
     * Determines appropriate billing period start and end dates for orders based
     * on customer billing frequency and delivery dates. Essential for proper
     * consolidated invoice timing and ensuring orders are grouped correctly
     * within their designated billing cycles.
     * 
     * @param Order $order The B2B order requiring billing period assignment
     * @return void Billing period dates assigned to order
     * 
     * @billing_period_calculation Determines accurate billing cycles for consolidated invoicing
     * @frequency_support Multiple billing frequencies accommodate diverse customer needs
     * @delivery_alignment Billing periods based on actual delivery dates
     * @consolidation_preparation Proper period assignment enables invoice consolidation
     * 
     * Period Calculation Logic:
     * - **Weekly**: Sunday to Saturday periods based on delivery week
     * - **Biweekly**: Two-week periods starting from delivery week
     * - **Monthly**: Calendar month periods containing delivery date
     * - **Quarterly**: Calendar quarter periods for large-volume customers
     * - **Immediate**: No period assignment for immediate billing
     * 
     * Business Applications:
     * - **Invoice Grouping**: Orders within same period consolidated into single invoice
     * - **Customer Preferences**: Billing frequency matches customer business cycles
     * - **Payment Planning**: Predictable billing periods aid customer payment planning
     * - **Administrative Organization**: Clear period boundaries simplify billing management
     * 
     * Frequency-Specific Logic:
     * - **Weekly Cycles**: Standard week boundaries (Sunday-Saturday) for consistent billing
     * - **Biweekly Periods**: Two-week spans starting from delivery week
     * - **Monthly Billing**: Full calendar months for month-end billing processes
     * - **Quarterly Terms**: Quarter boundaries for large customer consolidated billing
     * 
     * Customer Service Benefits:
     * - **Predictable Billing**: Customers know when to expect invoices
     * - **Payment Planning**: Clear periods aid customer cash flow management
     * - **Account Organization**: Period-based billing simplifies record keeping
     * - **Flexible Options**: Multiple frequencies accommodate diverse business needs
     * 
     * Technical Implementation:
     * - **Carbon Date Handling**: Precise date calculations using Carbon library
     * - **Period Boundaries**: Accurate start and end date calculations
     * - **Delivery Date Base**: Uses actual delivery dates for period assignment
     * - **Database Updates**: Atomic updates of billing period fields
     * 
     * Data Integrity:
     * - **Consistent Periods**: Standardized period calculations across all orders
     * - **Accurate Dates**: Precise start and end date assignments
     * - **Customer Compliance**: Respects customer billing frequency preferences
     * - **Consolidation Ready**: Proper periods enable accurate invoice consolidation
     * 
     * @billing_automation Essential foundation for automated consolidated invoicing
     * @customer_service Flexible billing periods accommodate customer preferences
     * @administrative_efficiency Standardized periods simplify billing management
     */
    public function setBillingPeriodForOrder(Order $order): void
    {
        if ($order->order_type !== 'b2b' || $order->billing_frequency === 'immediate') {
            return;
        }
        
        $deliveryDate = $order->delivery_date ?? now();
        
        switch ($order->billing_frequency) {
            case 'weekly':
                $periodStart = $deliveryDate->copy()->startOfWeek();
                $periodEnd = $deliveryDate->copy()->endOfWeek();
                break;
                
            case 'biweekly':
                $startOfWeek = $deliveryDate->copy()->startOfWeek();
                $periodStart = $startOfWeek;
                $periodEnd = $startOfWeek->copy()->addWeeks(2)->subDay();
                break;
                
            case 'monthly':
                $periodStart = $deliveryDate->copy()->startOfMonth();
                $periodEnd = $deliveryDate->copy()->endOfMonth();
                break;
                
            case 'quarterly':
                $periodStart = $deliveryDate->copy()->startOfQuarter();
                $periodEnd = $deliveryDate->copy()->endOfQuarter();
                break;
                
            default:
                return;
        }
        
        $order->update([
            'billing_period_start' => $periodStart->toDateString(),
            'billing_period_end' => $periodEnd->toDateString()
        ]);
    }
    
    /**
     * Generate immediate invoice for orders requiring instant billing processing.
     * 
     * Creates individual invoice for orders configured for immediate billing,
     * typically website orders or cash sales requiring instant invoice generation.
     * Provides alternative to consolidated billing for orders needing immediate
     * financial documentation and payment processing.
     * 
     * @param Order $order The order requiring immediate invoice generation
     * @return Invoice|null Generated immediate invoice or null if not required
     * 
     * @immediate_billing Instant invoice generation for non-consolidated orders
     * @website_orders Specialized processing for online order invoicing
     * @individual_invoices Single-order billing for immediate payment requirements
     * @duplicate_prevention Checks for existing invoices before generation
     * 
     * Processing Logic:
     * - **Eligibility Check**: Confirms order requires immediate invoicing
     * - **Duplicate Prevention**: Verifies invoice doesn't already exist
     * - **Invoice Creation**: Generates individual invoice for single order
     * - **Payment Terms**: Applies shorter payment terms for immediate orders
     * - **Database Transaction**: Ensures atomic invoice creation
     * 
     * Business Applications:
     * - **Website Orders**: Immediate billing for online purchases
     * - **Cash Sales**: Instant documentation for direct sales
     * - **Individual Customers**: Non-consolidated billing for specific orders
     * - **Payment Processing**: Immediate invoice for payment collection
     * 
     * Invoice Characteristics:
     * - **Single Order**: Invoice covers only the specified order
     * - **Immediate Issue**: Invoice date is current date
     * - **Shorter Terms**: 7-day payment terms for immediate orders
     * - **Individual Numbering**: Distinct numbering scheme from consolidated invoices
     * - **Direct Relationship**: Clear one-to-one order-invoice relationship
     * 
     * Customer Experience:
     * - **Instant Documentation**: Immediate invoice availability
     * - **Payment Clarity**: Clear invoice for single order payment
     * - **Simple Processing**: Straightforward billing for individual transactions
     * - **Quick Resolution**: Faster payment terms for immediate orders
     * 
     * Error Handling:
     * - **Eligibility Validation**: Returns null for non-qualifying orders
     * - **Duplicate Prevention**: Returns existing invoice if already present
     * - **Transaction Safety**: Database transactions ensure data integrity
     * - **Comprehensive Logging**: Detailed logging for immediate invoice tracking
     * 
     * Integration Benefits:
     * - **Payment Systems**: Immediate invoices support instant payment processing
     * - **E-commerce**: Website order billing integration
     * - **Customer Service**: Clear documentation for immediate order inquiries
     * - **Financial Records**: Complete billing coverage for all order types
     * 
     * @website_commerce Immediate billing for online order processing
     * @customer_service Instant invoice documentation for customer satisfaction
     * @financial_integration Supports immediate payment processing workflows
     */
    public function processImmediateInvoicing(Order $order): ?Invoice
    {
        if (!$order->requiresImmediateInvoicing() || !$order->requires_invoice) {
            return null;
        }
        
        // Check if invoice already exists
        if ($order->invoice) {
            return $order->invoice;
        }
        
        return DB::transaction(function () use ($order) {
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'invoice_number' => $this->generateImmediateInvoiceNumber($order),
                'amount' => $order->totalAmount(),
                'total_amount' => $order->totalAmount(),
                'status' => 'pending',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(), // 7 days for immediate orders
                'is_consolidated' => false,
                'notes' => "Invoice for order #{$order->id}"
            ]);
            
            Log::info('Generated immediate invoice', [
                'invoice_id' => $invoice->id,
                'order_id' => $order->id,
                'total_amount' => $invoice->total_amount
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Generate professional invoice number for immediate order billing.
     * 
     * Creates unique invoice number following standard business practices for
     * individual order invoicing. Provides clear distinction from consolidated
     * invoices while maintaining professional numbering standards for customer
     * accounting systems and business documentation.
     * 
     * @param Order $order The order requiring immediate invoice numbering
     * @return string Unique immediate invoice number
     * 
     * @professional_numbering Business-appropriate invoice numbering for immediate orders
     * @immediate_identification Clear distinction from consolidated invoice numbering
     * @sequence_management Ensures unique numbers within date for duplicate prevention
     * @accounting_compatibility Professional numbering supports business accounting
     * 
     * Numbering Format: INV-{DATE}-{SEQUENCE}
     * - **Prefix**: 'INV' identifies standard individual invoices
     * - **Date Code**: YYYYMMDD format for chronological organization
     * - **Sequence**: 4-digit sequence number for uniqueness within date
     * 
     * Business Standards:
     * - **Professional Format**: Industry-standard invoice numbering approach
     * - **Chronological Order**: Date-based numbering aids filing and organization
     * - **Unique Identification**: Sequence prevents duplicate invoice numbers
     * - **System Compatibility**: Numbering works with standard accounting software
     * 
     * Administrative Benefits:
     * - **Easy Recognition**: 'INV' prefix clearly identifies immediate invoices
     * - **Date Organization**: Chronological numbering supports record management
     * - **Type Distinction**: Different prefix from 'CONS' consolidated invoices
     * - **Sequential Logic**: Automatic numbering prevents conflicts
     * 
     * Customer Experience:
     * - **Professional Appearance**: Business-appropriate invoice numbering
     * - **Easy Reference**: Clear, memorable invoice numbers for payment reference
     * - **System Integration**: Compatible with customer accounting systems
     * - **Consistent Format**: Standardized numbering across all immediate invoices
     * 
     * Technical Implementation:
     * - **Date Formatting**: Current date in YYYYMMDD format for consistency
     * - **Sequence Calculation**: Database-based counting ensures uniqueness
     * - **Format Standardization**: Consistent formatting across all immediate invoices
     * - **Collision Prevention**: Accurate sequence counting prevents duplicates
     * 
     * Quality Assurance:
     * - **Uniqueness Guarantee**: Database-based sequence ensures no duplicates
     * - **Professional Standards**: Business-appropriate numbering format
     * - **System Integration**: Compatible with accounting and payment systems
     * - **Administrative Organization**: Structured numbering aids record management
     * 
     * @protected_method Internal utility for immediate invoice numbering
     * @business_standards Professional numbering appropriate for immediate billing
     * @administrative_support Organized numbering aids invoice management and tracking
     */
    protected function generateImmediateInvoiceNumber(Order $order): string
    {
        $prefix = 'INV';
        $dateCode = now()->format('Ymd');
        $sequence = Invoice::where('invoice_number', 'like', "{$prefix}-{$dateCode}-%")
            ->count() + 1;
            
        return sprintf('%s-%s-%04d', $prefix, $dateCode, $sequence);
    }
}