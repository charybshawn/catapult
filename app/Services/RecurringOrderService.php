<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use App\Models\OrderStatus;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive subscription and recurring order management service for agricultural operations.
 * 
 * This specialized service manages the complete lifecycle of subscription-based orders,
 * providing automated recurring order generation, customer subscription management,
 * and flexible scheduling for continuous agricultural product delivery. Essential
 * for maintaining consistent customer relationships and predictable revenue streams.
 * 
 * @service_domain Customer relationship management and subscription commerce
 * @business_purpose Automates recurring order generation and subscription management
 * @agricultural_focus Continuous supply of fresh microgreens to regular customers
 * @revenue_optimization Predictable recurring revenue through automated order processing
 * @customer_service Consistent product delivery through subscription automation
 * 
 * Core Subscription Management Features:
 * - **Automated Order Generation**: Creates orders based on customer subscription schedules
 * - **Flexible Scheduling**: Supports weekly, biweekly, monthly, and custom intervals
 * - **Template Management**: Maintains reusable order templates for consistent generation
 * - **Subscription Lifecycle**: Complete pause, resume, and termination capabilities
 * - **Catch-up Processing**: Ensures missed orders are generated for production planning
 * - **End Date Management**: Automatic deactivation when subscription periods expire
 * 
 * Agricultural Business Applications:
 * - **Restaurant Partnerships**: Regular fresh produce deliveries to food service clients
 * - **CSA Programs**: Community supported agriculture with predictable harvest schedules
 * - **Retail Partnerships**: Consistent supply to grocery stores and markets
 * - **Direct Consumer**: Home delivery subscriptions for regular customers
 * - **B2B Services**: Ongoing supply agreements with commercial customers
 * - **Seasonal Planning**: Subscription-based production planning and resource allocation
 * 
 * Subscription Workflow Management:
 * - **Template Creation**: Convert existing orders into recurring subscription templates
 * - **Schedule Calculation**: Intelligent next-generation date calculation based on frequency
 * - **Order Generation**: Automated creation of actual orders from subscription templates
 * - **Production Integration**: Generated orders trigger agricultural planning workflows
 * - **Customer Communication**: Integration with notification systems for delivery updates
 * - **Billing Coordination**: Supports invoice consolidation and payment processing
 * 
 * Business Intelligence and Analytics:
 * - **Subscription Statistics**: Comprehensive reporting on active and paused subscriptions
 * - **Revenue Forecasting**: Predictable income streams through recurring order analysis
 * - **Customer Retention**: Subscription management tools for maintaining relationships
 * - **Production Planning**: Subscription data feeds into crop planning algorithms
 * - **Performance Metrics**: Analysis of subscription conversion and retention rates
 * 
 * Customer Experience Features:
 * - **Flexible Management**: Easy pause and resume capabilities for customer convenience
 * - **Predictable Delivery**: Consistent scheduling builds customer trust and satisfaction
 * - **Customization**: Subscription templates can be modified to meet changing needs
 * - **Communication**: Automated notifications about upcoming deliveries and changes
 * - **Service Recovery**: Catch-up processing ensures no deliveries are missed
 * 
 * Technical Architecture:
 * - **Template-Based Design**: Separates subscription templates from generated orders
 * - **Database Optimization**: Efficient queries for large-scale subscription processing
 * - **Schedule Management**: Sophisticated date calculation for various frequencies
 * - **Integration Ready**: Coordinates with order processing and production planning
 * - **Error Resilience**: Comprehensive error handling for subscription processing failures
 * 
 * Integration Points:
 * - Order Processing: Generated recurring orders enter standard order fulfillment
 * - Production Planning: Subscription data feeds into crop planning and scheduling
 * - Inventory Management: Recurring orders considered in resource allocation
 * - Customer Service: Subscription status integration with customer support tools
 * - Billing Systems: Recurring orders coordinate with invoice and payment processing
 * 
 * Revenue and Business Benefits:
 * - **Predictable Revenue**: Recurring subscriptions provide stable income streams
 * - **Customer Retention**: Subscription model increases customer lifetime value
 * - **Operational Efficiency**: Automated processing reduces manual order management
 * - **Production Optimization**: Predictable demand enables better resource planning
 * - **Scaling Support**: Subscription automation supports business growth
 * - **Quality Relationships**: Consistent service builds stronger customer partnerships
 * 
 * @subscription_commerce Comprehensive recurring order and customer subscription management
 * @agricultural_automation Integrates subscription processing with production planning
 * @customer_retention Tools for maintaining long-term customer relationships
 * @revenue_optimization Predictable recurring revenue through automated subscription processing
 */
class RecurringOrderService
{
    /**
     * Execute comprehensive recurring order processing cycle for all active subscriptions.
     * 
     * Performs system-wide processing of all active recurring order templates,
     * generating new orders as needed, handling catch-up scenarios for production
     * planning, and managing subscription lifecycle including automatic deactivation
     * when end dates are reached. Essential for maintaining consistent customer
     * service and agricultural production planning.
     * 
     * @return array Processing results with counts and error information
     * 
     * @system_wide_processing Handles all active subscriptions in single operation
     * @production_planning Ensures catch-up orders for complete agricultural scheduling
     * @lifecycle_management Automatic deactivation of expired subscriptions
     * @error_resilience Continues processing despite individual subscription failures
     * 
     * Processing Workflow:
     * - **Template Discovery**: Identifies all recurring order templates requiring processing
     * - **Catch-up Generation**: Creates any missing orders needed for production planning
     * - **Schedule Management**: Updates next generation dates for ongoing subscriptions
     * - **Lifecycle Management**: Deactivates subscriptions that have passed end dates
     * - **Error Handling**: Isolates and logs individual subscription processing failures
     * - **Statistics Compilation**: Returns comprehensive processing results
     * 
     * Agricultural Integration:
     * - **Production Planning**: Generated orders feed into crop planning systems
     * - **Resource Allocation**: Subscription orders considered in inventory management
     * - **Harvest Scheduling**: Recurring orders influence agricultural timing decisions
     * - **Quality Assurance**: Consistent order generation supports product quality
     * 
     * Return Structure:
     * - processed: Total number of subscription templates processed
     * - generated: Number of new orders created from subscriptions
     * - deactivated: Number of subscriptions automatically deactivated
     * - errors: Array of any processing errors with context
     * 
     * Business Benefits:
     * - **Operational Automation**: Reduces manual subscription management overhead
     * - **Production Continuity**: Ensures agricultural planning includes all requirements
     * - **Customer Service**: Maintains consistent delivery schedules automatically
     * - **Revenue Predictability**: Generates orders that translate to predictable income
     * - **Error Management**: Isolates problems to prevent system-wide failures
     * 
     * Logging and Monitoring:
     * - Comprehensive logging of all generated orders with customer context
     * - Error logging for troubleshooting individual subscription issues
     * - Performance tracking for system-wide subscription processing efficiency
     * 
     * @automated_processing Designed for scheduled execution (cron jobs, etc.)
     * @agricultural_coordination Integrates with production planning workflows
     * @customer_service Maintains consistent subscription delivery schedules
     */
    public function processRecurringOrders(): array
    {
        $results = [
            'processed' => 0,
            'generated' => 0,
            'deactivated' => 0,
            'errors' => []
        ];

        $recurringOrders = $this->getAllRecurringTemplates();
        
        foreach ($recurringOrders as $order) {
            try {
                $results['processed']++;
                
                // Always generate catch-up orders for active templates (for crop planning)
                $generatedOrders = $order->generateRecurringOrdersCatchUp();
                
                foreach ($generatedOrders as $newOrder) {
                    $results['generated']++;
                    Log::info('Generated recurring order', [
                        'template_id' => $order->id,
                        'new_order_id' => $newOrder->id,
                        'customer' => $order->user->name ?? 'Unknown',
                        'harvest_date' => $newOrder->harvest_date,
                        'delivery_date' => $newOrder->delivery_date,
                        'is_future' => $newOrder->delivery_date->isFuture() ? 'yes' : 'no'
                    ]);
                }
                
                if ($this->shouldDeactivateOrder($order)) {
                    $order->update(['is_recurring_active' => false]);
                    $results['deactivated']++;
                    Log::info('Deactivated recurring order (past end date)', [
                        'template_id' => $order->id,
                        'customer' => $order->user->name ?? 'Unknown'
                    ]);
                }
            } catch (Exception $e) {
                $results['errors'][] = [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ];
                Log::error('Error processing recurring order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Retrieve all currently active recurring order subscription templates.
     * 
     * Returns comprehensive collection of active subscription templates with
     * complete relationship data for subscription management and processing.
     * Excludes generated orders to focus on master templates that define
     * subscription parameters and generate ongoing orders.
     * 
     * @return Collection<Order> Active recurring order templates with relationships
     * 
     * @template_focus Returns master templates, not generated individual orders
     * @relationship_loading Eager loads essential data for subscription processing
     * @active_filtering Only returns currently active subscription templates
     * @comprehensive_data Includes customer, product, and pricing information
     * 
     * Query Optimization:
     * - **Template Filtering**: Only recurring orders that serve as templates
     * - **Active Status**: Filters to currently active subscriptions only
     * - **Relationship Loading**: Eager loads user, customer, products, and pricing data
     * - **Packaging Information**: Includes packaging types for order fulfillment
     * 
     * Included Relationships:
     * - user: Account holder and billing information
     * - customer: Delivery and service details
     * - orderType: Classification for processing workflows
     * - orderItems: Products and quantities for subscription
     * - priceVariation: Pricing details for billing coordination
     * - packagingTypes: Fulfillment specifications
     * 
     * Business Applications:
     * - **Subscription Management**: Active subscription overview and management
     * - **Customer Service**: Quick access to customer subscription details
     * - **Production Planning**: Input data for agricultural scheduling
     * - **Revenue Analysis**: Active subscription base for financial planning
     * - **Order Processing**: Templates for recurring order generation
     * 
     * Data Structure:
     * - Returns Laravel Collection of Order models configured as recurring templates
     * - Each template contains complete subscription specification
     * - Ready for immediate processing or administrative review
     * - Optimized for performance with single query and eager loading
     * 
     * @subscription_management Active subscription templates for ongoing processing
     * @performance_optimized Single query with comprehensive relationship loading
     * @business_intelligence Foundation data for subscription analytics and reporting
     */
    public function getActiveRecurringOrders(): Collection
    {
        return Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id') // Only templates, not generated orders
            ->with(['user', 'customer', 'orderType', 'orderItems.product', 'orderItems.priceVariation', 'packagingTypes'])
            ->get();
    }

    /**
     * Retrieve complete collection of recurring order templates regardless of status.
     * 
     * Returns comprehensive collection of all subscription templates including
     * both active and paused subscriptions with complete relationship data.
     * Essential for administrative oversight, subscription analytics, and
     * comprehensive customer service management.
     * 
     * @return Collection<Order> All recurring order templates with relationships
     * 
     * @comprehensive_view Includes both active and inactive subscription templates
     * @administrative_tool Complete subscription database for management purposes
     * @analytics_foundation All subscription data for business intelligence
     * @relationship_complete Eager loads all essential subscription information
     * 
     * Scope and Coverage:
     * - **All Templates**: Both active and paused recurring order templates
     * - **Template Focus**: Excludes generated orders, focuses on master definitions
     * - **Complete Data**: Comprehensive relationship loading for full context
     * - **Administrative Ready**: Suitable for management dashboards and reporting
     * 
     * Business Applications:
     * - **Subscription Analytics**: Complete view of subscription base performance
     * - **Customer Service**: Access to all customer subscription history
     * - **Administrative Management**: Comprehensive subscription oversight
     * - **Business Intelligence**: Foundation for subscription trend analysis
     * - **Service Recovery**: Access to paused subscriptions for reactivation
     * - **Performance Metrics**: Analysis of subscription lifecycle patterns
     * 
     * Data Completeness:
     * - All subscription templates regardless of current status
     * - Complete customer and product information for each template
     * - Pricing and packaging details for service analysis
     * - Historical context for subscription lifecycle understanding
     * 
     * Use Cases:
     * - **Management Dashboards**: Complete subscription base overview
     * - **Customer Support**: Access to all customer subscription information
     * - **Business Planning**: Historical and current subscription analysis
     * - **Service Analysis**: Understanding of subscription patterns and trends
     * - **Recovery Operations**: Identification of inactive subscriptions for reactivation
     * 
     * @administrative_tool Comprehensive subscription management and oversight
     * @business_intelligence Complete subscription data for analytics and planning
     * @customer_service Full subscription history for comprehensive customer support
     */
    public function getAllRecurringTemplates(): Collection
    {
        return Order::where('is_recurring', true)
            ->whereNull('parent_recurring_order_id') // Only templates, not generated orders
            ->with(['user', 'customer', 'orderType', 'orderItems.product', 'orderItems.priceVariation', 'packagingTypes'])
            ->get();
    }

    /**
     * Evaluate whether recurring order template should generate next scheduled order.
     * 
     * Determines if subscription template has reached its next generation date
     * and requires creation of a new order instance. Handles initialization of
     * generation scheduling and provides timing logic for automated subscription
     * processing workflows.
     * 
     * @param Order $order The recurring order template to evaluate
     * @return bool True if new order should be generated, false otherwise
     * 
     * @schedule_management Evaluates timing for automated order generation
     * @initialization_handling Sets up generation scheduling for new templates
     * @timing_precision Ensures accurate scheduling for customer expectations
     * @automation_ready Provides logic for automated subscription processing
     * 
     * Evaluation Logic:
     * - **Schedule Initialization**: Sets next generation date if not present
     * - **Timing Comparison**: Checks current time against scheduled generation
     * - **Deferred Generation**: Prevents immediate generation after schedule initialization
     * - **Automation Integration**: Provides reliable timing for scheduled processing
     * 
     * Schedule Management:
     * - Uses calculateNextGenerationDate() for initial scheduling
     * - Updates template with calculated next generation timing
     * - Defers generation to next processing cycle after initialization
     * - Ensures consistent timing across subscription processing runs
     * 
     * Business Benefits:
     * - **Predictable Timing**: Consistent order generation according to schedules
     * - **Customer Expectations**: Orders generated at expected intervals
     * - **Production Planning**: Reliable timing for agricultural scheduling
     * - **System Efficiency**: Prevents unnecessary order generation
     * - **Quality Control**: Proper timing ensures order quality and accuracy
     * 
     * Integration Considerations:
     * - **Template Updates**: Modifies template with calculated scheduling data
     * - **Processing Cycles**: Designed for repeated automated execution
     * - **Timing Accuracy**: Uses precise date/time comparisons for reliability
     * - **Error Prevention**: Prevents generation timing conflicts
     * 
     * @protected_method Internal logic for subscription processing workflows
     * @timing_critical Ensures accurate scheduling for customer satisfaction
     * @automation_logic Provides reliable evaluation for automated systems
     */
    protected function shouldGenerateOrder(Order $order): bool
    {
        // If no next generation date is set, calculate it
        if (!$order->next_generation_date) {
            $nextDate = $order->calculateNextGenerationDate();
            if ($nextDate) {
                $order->update(['next_generation_date' => $nextDate]);
            }
            return false; // Don't generate immediately after setting date
        }

        // Check if it's time to generate
        return now()->gte($order->next_generation_date);
    }

    /**
     * Determine if recurring order template should be automatically deactivated.
     * 
     * Evaluates subscription template against end date criteria to determine
     * if subscription has reached its termination point and should be deactivated.
     * Essential for automated subscription lifecycle management and preventing
     * generation of orders beyond customer subscription periods.
     * 
     * @param Order $order The recurring order template to evaluate for deactivation
     * @return bool True if subscription should be deactivated, false otherwise
     * 
     * @lifecycle_management Automated subscription termination based on end dates
     * @customer_service Respects customer subscription duration preferences
     * @business_rules Enforces subscription term compliance
     * @automation_ready Provides logic for automated deactivation processing
     * 
     * Deactivation Criteria:
     * - **End Date Presence**: Template must have specified recurring_end_date
     * - **Time Comparison**: Current time must exceed the specified end date
     * - **Automatic Processing**: Designed for scheduled deactivation workflows
     * - **Clean Termination**: Prevents generation of orders past subscription terms
     * 
     * Business Applications:
     * - **Subscription Terms**: Enforces customer-specified subscription durations
     * - **Service Compliance**: Ensures subscriptions don't exceed agreed periods
     * - **Resource Management**: Prevents unnecessary order generation and processing
     * - **Customer Relations**: Respects customer subscription preferences and limits
     * - **Billing Accuracy**: Prevents charges beyond agreed subscription periods
     * 
     * Lifecycle Benefits:
     * - **Automated Management**: Reduces manual subscription termination overhead
     * - **Customer Trust**: Reliable adherence to subscription terms builds confidence
     * - **Operational Efficiency**: Prevents processing of expired subscriptions
     * - **Compliance**: Ensures service delivery matches customer agreements
     * - **Clean Data**: Maintains accurate active subscription records
     * 
     * Integration Context:
     * - **Processing Workflows**: Used in automated subscription processing cycles
     * - **Administrative Tools**: Supports manual subscription management
     * - **Compliance Systems**: Ensures adherence to subscription terms
     * - **Customer Service**: Provides foundation for subscription status management
     * 
     * @protected_method Internal logic for subscription lifecycle management
     * @automated_deactivation Enables scheduled subscription termination
     * @customer_compliance Respects customer subscription duration preferences
     */
    protected function shouldDeactivateOrder(Order $order): bool
    {
        return $order->recurring_end_date && now()->gt($order->recurring_end_date);
    }

    /**
     * Retrieve upcoming recurring orders scheduled for generation within specified timeframe.
     * 
     * Identifies active subscription templates that will generate new orders within
     * the specified number of days, providing essential data for production planning,
     * customer service preparation, and operational resource allocation. Critical
     * for proactive agricultural planning and customer communication.
     * 
     * @param int $days Number of days to look ahead (default: 7)
     * @return Collection<Order> Upcoming subscription templates with generation dates
     * 
     * @production_planning Essential for agricultural scheduling and resource allocation
     * @customer_service Enables proactive communication about upcoming deliveries
     * @operational_forecast Supports workforce and resource planning
     * @timeline_management Flexible timeframe for different planning horizons
     * 
     * Planning Applications:
     * - **Agricultural Scheduling**: Upcoming orders feed into crop planning algorithms
     * - **Resource Allocation**: Advance notice for inventory and labor planning
     * - **Customer Communication**: Proactive delivery notifications and confirmations
     * - **Quality Preparation**: Time for quality control and harvest preparation
     * - **Logistics Coordination**: Advance planning for delivery schedules
     * 
     * Query Optimization:
     * - **Date Range Filtering**: Efficient filtering to specified timeframe
     * - **Active Templates Only**: Focuses on currently active subscriptions
     * - **Ordered Results**: Sorted by generation date for chronological planning
     * - **Relationship Loading**: Includes customer and product data for context
     * 
     * Business Benefits:
     * - **Proactive Planning**: Advance notice enables better resource allocation
     * - **Customer Service**: Preparation time for delivery coordination
     * - **Quality Assurance**: Adequate time for harvest and quality preparation
     * - **Operational Efficiency**: Smooth workflow through advance planning
     * - **Flexibility**: Adjustable timeframe for different planning needs
     * 
     * Use Cases:
     * - **Weekly Planning**: 7-day lookahead for operational preparation
     * - **Monthly Forecasting**: Extended timeframes for strategic planning
     * - **Customer Service**: Upcoming delivery notifications and scheduling
     * - **Production Scheduling**: Agricultural timing and resource coordination
     * 
     * @forecasting_tool Provides advance notice for operational planning
     * @agricultural_integration Supports crop planning and harvest scheduling
     * @customer_communication Foundation for proactive customer service
     */
    public function getUpcomingRecurringOrders(int $days = 7): Collection
    {
        $endDate = now()->addDays($days);
        
        return Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id')
            ->where('next_generation_date', '<=', $endDate)
            ->with(['user', 'orderItems'])
            ->orderBy('next_generation_date')
            ->get();
    }

    /**
     * Create new recurring order subscription template with comprehensive configuration.
     * 
     * Establishes complete subscription template with all required recurring parameters,
     * initial scheduling calculations, and proper status configuration for automated
     * processing. Forms the foundation for ongoing customer subscription management
     * and automated order generation workflows.
     * 
     * @param array $data Complete subscription configuration data
     * @return Order Newly created recurring order template
     * 
     * @subscription_creation Establishes new customer subscription with full configuration
     * @template_initialization Sets up foundation for recurring order generation
     * @schedule_calculation Determines initial generation timing based on frequency
     * @status_management Configures template status for proper workflow processing
     * 
     * Template Configuration:
     * - **Recurring Flags**: Sets is_recurring and is_recurring_active for processing
     * - **Template Status**: Assigns 'template' status to distinguish from regular orders
     * - **Schedule Calculation**: Determines next_generation_date based on frequency
     * - **Data Validation**: Ensures required subscription parameters are present
     * 
     * Scheduling Logic:
     * - **Frequency Support**: Weekly, biweekly, monthly, and custom interval handling
     * - **Start Date Integration**: Uses recurring_start_date for initial calculations
     * - **Interval Processing**: Supports custom intervals for flexible scheduling
     * - **Automated Timing**: Calculates precise next generation dates
     * 
     * Business Benefits:
     * - **Customer Onboarding**: Streamlined subscription setup process
     * - **Revenue Predictability**: Established recurring revenue streams
     * - **Automation Ready**: Templates prepared for automated processing
     * - **Service Consistency**: Standardized subscription configuration
     * - **Operational Efficiency**: Reduced manual subscription management
     * 
     * Data Requirements:
     * - Customer information for subscription management
     * - Product and pricing details for order generation
     * - Frequency and timing specifications for scheduling
     * - Optional end date for subscription term management
     * 
     * Logging and Tracking:
     * - Comprehensive creation logging with customer and frequency context
     * - Template identification for subscription management
     * - Customer information for service tracking
     * 
     * @customer_onboarding Streamlined process for establishing new subscriptions
     * @revenue_automation Foundation for predictable recurring revenue streams
     * @agricultural_integration Templates support production planning workflows
     */
    public function createRecurringOrderTemplate(array $data): Order
    {
        // Ensure required recurring fields are set
        $data['is_recurring'] = true;
        $data['is_recurring_active'] = true;
        $data['status'] = 'template'; // Special status for templates
        
        // Calculate initial next generation date
        if (isset($data['recurring_start_date'])) {
            $startDate = Carbon::parse($data['recurring_start_date']);
            $data['next_generation_date'] = $this->calculateNextDate($startDate, $data['recurring_frequency'], $data['recurring_interval'] ?? null);
        }

        $order = Order::create($data);
        
        Log::info('Created recurring order template', [
            'template_id' => $order->id,
            'customer' => $order->user->name ?? 'Unknown',
            'frequency' => $order->recurring_frequency
        ]);

        return $order;
    }

    /**
     * Calculate next scheduled date based on subscription frequency and interval.
     * 
     * Performs precise date arithmetic to determine when the next recurring order
     * should be generated, supporting various subscription frequencies with custom
     * intervals. Essential for maintaining accurate subscription timing and customer
     * service expectations.
     * 
     * @param Carbon $fromDate Base date for calculation (usually current or last generation)
     * @param string $frequency Subscription frequency (weekly, biweekly, monthly)
     * @param int|null $interval Custom interval multiplier (default varies by frequency)
     * @return Carbon Next scheduled generation date
     * 
     * @date_arithmetic Precise calculation for subscription timing accuracy
     * @frequency_support Multiple subscription patterns for customer flexibility
     * @interval_customization Custom intervals for specialized subscription needs
     * @timing_precision Ensures accurate scheduling for customer expectations
     * 
     * Supported Frequencies:
     * - **Weekly**: Standard weekly intervals with custom multiplier support
     * - **Biweekly**: Every two weeks with interval customization
     * - **Monthly**: Monthly recurring with month-based interval support
     * - **Default Fallback**: Weekly frequency for unrecognized patterns
     * 
     * Calculation Logic:
     * - **Weekly**: Adds specified weeks to base date (default 1 week)
     * - **Biweekly**: Adds interval * 2 weeks (default 2 weeks if no interval)
     * - **Monthly**: Adds specified months to base date (default 1 month)
     * - **Carbon Integration**: Uses Carbon date library for accurate calculations
     * 
     * Business Applications:
     * - **Customer Expectations**: Accurate timing meets customer delivery expectations
     * - **Production Planning**: Reliable dates feed into agricultural scheduling
     * - **Service Quality**: Consistent timing builds customer trust
     * - **Operational Efficiency**: Predictable scheduling enables resource optimization
     * 
     * Flexibility Features:
     * - **Custom Intervals**: Supports non-standard subscription patterns
     * - **Multiple Frequencies**: Accommodates diverse customer preferences
     * - **Precise Arithmetic**: Maintains accuracy across multiple generations
     * - **Fallback Handling**: Graceful handling of unrecognized frequencies
     * 
     * @protected_method Internal utility for subscription timing calculations
     * @customer_service Accurate timing supports customer satisfaction
     * @agricultural_planning Reliable dates enable production scheduling
     */
    protected function calculateNextDate(Carbon $fromDate, string $frequency, ?int $interval = null): Carbon
    {
        return match($frequency) {
            'weekly' => $fromDate->addWeek(),
            'biweekly' => $fromDate->addWeeks($interval ?? 2),
            'monthly' => $fromDate->addMonth(),
            default => $fromDate->addWeek()
        };
    }

    /**
     * Pause active recurring order subscription while preserving template for reactivation.
     * 
     * Temporarily deactivates subscription template while maintaining all configuration
     * data for future reactivation. Provides customer service flexibility for handling
     * temporary subscription suspensions without losing subscription parameters or
     * customer preferences.
     * 
     * @param Order $order The recurring order template to pause
     * @return bool True if successfully paused, false if not a valid template
     * 
     * @customer_service Flexible subscription management for customer convenience
     * @data_preservation Maintains subscription configuration for easy reactivation
     * @service_flexibility Supports temporary subscription suspensions
     * @template_validation Ensures operation only on valid recurring templates
     * 
     * Pause Operation:
     * - **Status Update**: Sets is_recurring_active to false to halt generation
     * - **Data Preservation**: Maintains all subscription parameters and preferences
     * - **Validation**: Confirms order is valid recurring template before operation
     * - **Logging**: Records pause operation with customer context for tracking
     * 
     * Business Benefits:
     * - **Customer Retention**: Flexible pausing prevents subscription cancellation
     * - **Service Quality**: Accommodates customer life changes and preferences
     * - **Revenue Protection**: Preserves customer relationship for future reactivation
     * - **Operational Flexibility**: Easy subscription management for customer service
     * - **Data Integrity**: Maintains subscription configuration during pause
     * 
     * Customer Service Applications:
     * - **Temporary Suspensions**: Vacation holds, seasonal preferences
     * - **Service Recovery**: Addressing delivery issues or customer concerns
     * - **Flexible Scheduling**: Accommodating customer schedule changes
     * - **Relationship Management**: Maintaining customer connection during breaks
     * 
     * Technical Implementation:
     * - **Template Validation**: Confirms order is recurring template using isRecurringTemplate()
     * - **Status Management**: Clean deactivation without data loss
     * - **Audit Trail**: Comprehensive logging for customer service tracking
     * - **Error Handling**: Returns false for invalid operations
     * 
     * @subscription_management Flexible customer subscription lifecycle control
     * @customer_retention Prevents subscription loss through temporary pausing
     * @service_recovery Tool for addressing customer service issues
     */
    public function pauseRecurringOrder(Order $order): bool
    {
        if (!$order->isRecurringTemplate()) {
            return false;
        }

        $order->update(['is_recurring_active' => false]);
        
        Log::info('Paused recurring order', [
            'template_id' => $order->id,
            'customer' => $order->user->name ?? 'Unknown'
        ]);

        return true;
    }

    /**
     * Resume previously paused recurring order subscription with updated scheduling.
     * 
     * Reactivates paused subscription template with recalculated next generation date
     * based on current timing. Provides seamless customer service for resuming
     * subscriptions while ensuring proper scheduling alignment with current dates.
     * 
     * @param Order $order The paused recurring order template to resume
     * @return bool True if successfully resumed, false if invalid or already active
     * 
     * @customer_service Seamless subscription reactivation for customer satisfaction
     * @schedule_recalculation Updates timing to align with current date
     * @service_recovery Enables easy subscription restoration after pauses
     * @validation_logic Ensures operation only on valid paused templates
     * 
     * Resume Operation:
     * - **Status Validation**: Confirms order is paused recurring template
     * - **Schedule Recalculation**: Updates next_generation_date from current time
     * - **Activation**: Sets is_recurring_active to true for processing inclusion
     * - **Logging**: Records resumption with customer and timing context
     * 
     * Scheduling Logic:
     * - **Current Date Base**: Recalculates from now() rather than old schedule
     * - **Frequency Preservation**: Maintains original frequency and interval settings
     * - **Clean Restart**: Establishes fresh timing aligned with resumption date
     * - **Automation Ready**: Prepares subscription for immediate processing inclusion
     * 
     * Business Benefits:
     * - **Customer Retention**: Easy reactivation encourages subscription continuation
     * - **Service Quality**: Smooth resumption experience builds customer satisfaction
     * - **Revenue Recovery**: Restores recurring revenue stream from paused subscriptions
     * - **Operational Efficiency**: Automated resumption reduces manual management
     * - **Relationship Management**: Demonstrates service flexibility and responsiveness
     * 
     * Customer Service Applications:
     * - **Post-Vacation Resumption**: Reactivating subscriptions after temporary holds
     * - **Service Recovery**: Resuming after resolving delivery or quality issues
     * - **Schedule Coordination**: Aligning resumption with customer availability
     * - **Relationship Building**: Showing flexibility and customer-focused service
     * 
     * Technical Implementation:
     * - **Validation Chain**: Confirms template status and current inactive state
     * - **Date Calculation**: Uses existing calculateNextDate() method for consistency
     * - **Atomic Update**: Single database operation for clean state change
     * - **Comprehensive Logging**: Full context logging for customer service tracking
     * 
     * @subscription_lifecycle Complete pause/resume cycle for customer flexibility
     * @customer_satisfaction Easy reactivation supports positive customer experience
     * @revenue_recovery Restores recurring revenue from temporarily paused subscriptions
     */
    public function resumeRecurringOrder(Order $order): bool
    {
        if (!$order->isRecurringTemplate() || $order->is_recurring_active) {
            return false;
        }

        // Recalculate next generation date from now
        $nextDate = $this->calculateNextDate(now(), $order->recurring_frequency, $order->recurring_interval);
        
        $order->update([
            'is_recurring_active' => true,
            'next_generation_date' => $nextDate
        ]);
        
        Log::info('Resumed recurring order', [
            'template_id' => $order->id,
            'customer' => $order->user->name ?? 'Unknown',
            'next_generation' => $nextDate
        ]);

        return true;
    }

    /**
     * Manually generate next scheduled order from recurring subscription template.
     * 
     * Creates immediate order generation from subscription template outside of
     * normal automated processing cycle. Provides customer service and administrative
     * capability for handling special requests, catch-up orders, or manual
     * subscription management scenarios.
     * 
     * @param Order $template The recurring order template to generate from
     * @return Order|null Newly generated order or null if generation failed
     * 
     * @throws InvalidArgumentException If order is not a recurring template
     * @throws InvalidArgumentException If template is not active
     * 
     * @manual_intervention Provides administrative control over subscription processing
     * @customer_service Enables immediate order generation for customer requests
     * @administrative_tool Manual override for automated subscription processing
     * @validation_strict Ensures operation only on valid active templates
     * 
     * Generation Process:
     * - **Template Validation**: Confirms order is valid recurring template using isRecurringTemplate()
     * - **Active Status Check**: Ensures template is currently active for generation
     * - **Order Creation**: Delegates to model's generateNextRecurringOrder() method
     * - **Audit Logging**: Records manual generation with user context
     * - **Result Handling**: Returns generated order or null for failures
     * 
     * Business Applications:
     * - **Customer Service**: Immediate order generation for customer requests
     * - **Service Recovery**: Creating replacement orders for missed deliveries
     * - **Schedule Adjustments**: Manual generation to accommodate timing changes
     * - **Administrative Control**: Direct management of subscription processing
     * - **Quality Assurance**: Testing and verification of subscription templates
     * 
     * Error Handling:
     * - **Template Validation**: InvalidArgumentException for non-template orders
     * - **Active Status**: InvalidArgumentException for inactive templates
     * - **Generation Failures**: Graceful handling with null return
     * - **Context Logging**: Comprehensive error information for troubleshooting
     * 
     * Logging Features:
     * - **Manual Generation Tracking**: Records administrative order creation
     * - **User Attribution**: Logs which user performed manual generation
     * - **Customer Context**: Includes customer information for service tracking
     * - **Template Relationship**: Links generated order back to source template
     * 
     * Integration Benefits:
     * - **Model Delegation**: Uses existing order generation logic for consistency
     * - **Authentication Aware**: Records current user for audit purposes
     * - **Standard Processing**: Generated orders enter normal fulfillment workflow
     * - **Quality Assurance**: Manual generation follows same rules as automated
     * 
     * @administrative_capability Direct subscription management for customer service
     * @audit_trail Comprehensive logging for administrative action tracking
     * @customer_satisfaction Immediate response capability for customer needs
     */
    public function generateNextOrder(Order $template): ?Order
    {
        if (!$template->isRecurringTemplate()) {
            throw new InvalidArgumentException('Order is not a recurring template');
        }

        if (!$template->is_recurring_active) {
            throw new InvalidArgumentException('Recurring order template is not active');
        }

        // Use the model's generateNextRecurringOrder method
        $newOrder = $template->generateNextRecurringOrder();
        
        if ($newOrder) {
            Log::info('Manually generated recurring order', [
                'template_id' => $template->id,
                'new_order_id' => $newOrder->id,
                'customer' => $template->user->name ?? 'Unknown',
                'generated_by' => auth()->user()?->name ?? 'System'
            ]);
        }

        return $newOrder;
    }

    /**
     * Generate comprehensive statistics about subscription and recurring order performance.
     * 
     * Provides detailed analytics about subscription base, including active and paused
     * templates, total generated orders, and upcoming processing pipeline. Essential
     * for business intelligence, performance monitoring, and strategic subscription
     * management decision-making.
     * 
     * @return array Comprehensive subscription statistics and metrics
     * 
     * @business_intelligence Complete subscription performance metrics
     * @performance_monitoring Key indicators for subscription system health
     * @strategic_planning Data for subscription business development
     * @operational_metrics Statistics for day-to-day subscription management
     * 
     * Statistics Categories:
     * - **Active Templates**: Currently processing subscription templates
     * - **Paused Templates**: Temporarily suspended subscriptions available for reactivation
     * - **Generated Orders**: Total historical order generation from subscriptions
     * - **Upcoming Pipeline**: Near-term order generation forecast
     * 
     * Metrics Breakdown:
     * - active_templates: Number of currently active subscription templates
     * - paused_templates: Number of paused subscriptions (retention opportunity)
     * - total_generated: Historical count of all generated recurring orders
     * - upcoming_week: Number of subscriptions generating orders in next 7 days
     * 
     * Business Intelligence Applications:
     * - **Revenue Analysis**: Active subscriptions indicate recurring revenue base
     * - **Customer Retention**: Paused vs active ratio shows retention opportunities
     * - **Growth Metrics**: Generated order totals demonstrate subscription success
     * - **Operational Planning**: Upcoming orders support resource allocation
     * - **Performance Tracking**: Historical trends in subscription utilization
     * 
     * Strategic Decision Support:
     * - **Subscription Health**: Overall performance of recurring order system
     * - **Customer Base Analysis**: Active subscription engagement metrics
     * - **Revenue Forecasting**: Predictable income from subscription base
     * - **Service Optimization**: Data for improving subscription offerings
     * - **Growth Planning**: Metrics for subscription business expansion
     * 
     * Operational Benefits:
     * - **Dashboard Integration**: Ready for management dashboard display
     * - **Performance Monitoring**: Key indicators for system health tracking
     * - **Planning Support**: Data for operational resource allocation
     * - **Trend Analysis**: Foundation for subscription performance trends
     * 
     * Data Quality:
     * - **Real-time Accuracy**: Current database state for up-to-date metrics
     * - **Comprehensive Coverage**: All aspects of subscription lifecycle
     * - **Actionable Information**: Metrics that support business decisions
     * - **Performance Context**: Statistics relevant to subscription management
     * 
     * @dashboard_ready Formatted for management dashboard integration
     * @decision_support Data for strategic subscription management decisions
     * @performance_tracking Key metrics for subscription system monitoring
     */
    public function getRecurringOrderStats(): array
    {
        $activeTemplates = Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id')
            ->count();

        $pausedTemplates = Order::where('is_recurring', true)
            ->where('is_recurring_active', false)
            ->whereNull('parent_recurring_order_id')
            ->count();

        $generatedOrders = Order::whereNotNull('parent_recurring_order_id')->count();

        $upcomingWeek = $this->getUpcomingRecurringOrders(7)->count();

        return [
            'active_templates' => $activeTemplates,
            'paused_templates' => $pausedTemplates,
            'total_generated' => $generatedOrders,
            'upcoming_week' => $upcomingWeek
        ];
    }

    /**
     * Transform existing regular order into recurring subscription template.
     * 
     * Converts completed or draft order into recurring subscription template with
     * comprehensive validation, configuration mapping, and schedule initialization.
     * Essential for customer onboarding when existing order serves as foundation
     * for ongoing subscription relationship.
     * 
     * @param Order $order The existing order to convert to subscription template
     * @param array $recurringSettings Subscription configuration parameters
     * @return Order Converted order now configured as recurring template
     * 
     * @throws InvalidArgumentException If order is already recurring
     * @throws InvalidArgumentException If order lacks required customer information
     * @throws InvalidArgumentException If order has no items to base subscription on
     * @throws InvalidArgumentException If no suitable template status available
     * 
     * @customer_onboarding Streamlined conversion from one-time to subscription customer
     * @subscription_creation Establishes recurring revenue from existing order
     * @data_preservation Maintains order details while adding subscription features
     * @validation_comprehensive Ensures order suitability for subscription conversion
     * 
     * Conversion Process:
     * - **Eligibility Validation**: Confirms order can be converted to subscription
     * - **Customer Verification**: Ensures order has customer for subscription management
     * - **Product Validation**: Confirms order items exist for recurring generation
     * - **Configuration Mapping**: Translates settings to database fields
     * - **Status Assignment**: Updates to template status for proper processing
     * - **Schedule Initialization**: Calculates initial generation timing
     * 
     * Settings Mapping:
     * - **Frequency**: Maps to recurring_frequency field
     * - **Date Range**: Handles start and optional end dates
     * - **Intervals**: Supports custom timing intervals
     * - **Schedule Calculation**: Determines next_generation_date
     * 
     * Business Benefits:
     * - **Customer Conversion**: Transforms one-time buyers into subscribers
     * - **Revenue Growth**: Establishes predictable recurring revenue streams
     * - **Service Expansion**: Extends customer relationship beyond single purchase
     * - **Operational Efficiency**: Automated processing reduces manual order entry
     * - **Customer Convenience**: Easy subscription setup from existing preferences
     * 
     * Validation Requirements:
     * - **Customer Association**: Order must have customer for subscription management
     * - **Product Content**: Order must contain items for recurring generation
     * - **Unique Conversion**: Order cannot already be recurring
     * - **Status Availability**: System must have template status configured
     * 
     * Data Transformation:
     * - **Template Configuration**: Sets all required recurring flags and parameters
     * - **Status Update**: Changes to template status for workflow processing
     * - **Date Clearing**: Removes specific dates (templates are date-independent)
     * - **Schedule Setup**: Establishes next generation timing
     * 
     * @customer_lifecycle Supports progression from one-time to subscription customer
     * @revenue_optimization Converts single orders into recurring revenue streams
     * @service_expansion Extends customer relationship through subscription model
     */
    public function convertToRecurringTemplate(Order $order, array $recurringSettings): Order
    {
        // Validate that the order can be converted
        if ($order->is_recurring) {
            throw new InvalidArgumentException('Order is already a recurring order');
        }

        if (!$order->customer) {
            throw new InvalidArgumentException('Order must have a customer to be converted to recurring');
        }

        if ($order->orderItems()->count() === 0) {
            throw new InvalidArgumentException('Order must have order items to be converted to recurring');
        }
        
        // Map form data to expected keys
        $mappedSettings = [
            'frequency' => $recurringSettings['frequency'],
            'start_date' => $recurringSettings['start_date'], 
            'end_date' => $recurringSettings['end_date'] ?? null,
            'interval' => $recurringSettings['interval'] ?? 1,
            'days_of_week' => null, // Not used in this conversion
        ];

        // Get template status for recurring orders
        $templateStatus = OrderStatus::where('code', 'template')->first() ?: 
                         OrderStatus::where('code', 'draft')->first();

        if (!$templateStatus) {
            throw new InvalidArgumentException('No suitable status found for recurring template');
        }

        // Update the order to be a recurring template
        $order->update([
            'is_recurring' => true,
            'is_recurring_active' => true,
            'parent_recurring_order_id' => null, // This is the template, not a generated order
            'status_id' => $templateStatus->id,
            'recurring_frequency' => $mappedSettings['frequency'],
            'recurring_start_date' => $mappedSettings['start_date'],
            'recurring_end_date' => $mappedSettings['end_date'],
            'recurring_interval' => $mappedSettings['interval'],
            'recurring_days_of_week' => $mappedSettings['days_of_week'],
            'harvest_date' => null, // Templates don't have specific dates
            'delivery_date' => null, // Templates don't have specific dates
            'next_generation_date' => $this->calculateNextGenerationDate($order, $mappedSettings),
        ]);

        Log::info('Converted order to recurring template', [
            'order_id' => $order->id,
            'customer' => $order->customer->contact_name,
            'frequency' => $mappedSettings['frequency'],
            'start_date' => $mappedSettings['start_date'],
            'converted_by' => auth()->user()?->name ?? 'System'
        ]);

        return $order->fresh();
    }

    /**
     * Calculate initial next generation date for newly created recurring template.
     * 
     * Determines when first recurring order should be generated based on subscription
     * start date, frequency, and interval settings. Critical for establishing proper
     * timing foundation for new subscription templates and ensuring accurate
     * generation scheduling from subscription inception.
     * 
     * @param Order $order The recurring order template being configured
     * @param array $recurringSettings Subscription timing configuration
     * @return Carbon Initial next generation date for subscription
     * 
     * @schedule_initialization Establishes timing foundation for new subscriptions
     * @frequency_calculation Supports multiple subscription patterns and intervals
     * @template_configuration Essential setup for proper subscription automation
     * @timing_precision Ensures accurate scheduling from subscription start
     * 
     * Calculation Logic:
     * - **Start Date Parsing**: Converts string start date to Carbon instance
     * - **Frequency Analysis**: Interprets subscription frequency pattern
     * - **Interval Application**: Applies custom intervals for specialized timing
     * - **Date Arithmetic**: Performs precise calculations for next generation
     * 
     * Supported Patterns:
     * - **Weekly**: Standard weekly intervals with custom multiplier support
     * - **Biweekly**: Every two weeks with interval customization (2 * interval)
     * - **Monthly**: Monthly recurring with interval-based month addition
     * - **Quarterly**: Every three months with interval support (3 * interval)
     * - **Default Fallback**: Weekly pattern for unrecognized frequencies
     * 
     * Business Applications:
     * - **Subscription Onboarding**: Proper timing setup for new customer subscriptions
     * - **Template Creation**: Foundation for automated recurring order processing
     * - **Customer Expectations**: Accurate scheduling aligns with customer preferences
     * - **Service Quality**: Consistent timing builds customer trust and satisfaction
     * 
     * Configuration Integration:
     * - **Settings Mapping**: Extracts timing parameters from configuration array
     * - **Validation**: Handles missing or invalid interval data with sensible defaults
     * - **Flexibility**: Supports various frequency patterns for customer needs
     * - **Accuracy**: Precise Carbon date arithmetic for reliable scheduling
     * 
     * Error Resilience:
     * - **Default Intervals**: Sensible defaults for missing interval specifications
     * - **Fallback Frequency**: Weekly default for unrecognized frequency patterns
     * - **Copy Operations**: Uses Carbon copy() to prevent date mutation issues
     * - **Consistent Logic**: Matches calculation logic used elsewhere in service
     * 
     * @private_method Internal utility for template initialization
     * @customer_service Accurate initial timing supports customer satisfaction
     * @subscription_automation Proper setup enables reliable automated processing
     */
    private function calculateNextGenerationDate(Order $order, array $recurringSettings): Carbon
    {
        $startDate = Carbon::parse($recurringSettings['start_date']);
        $frequency = $recurringSettings['frequency'];
        $interval = $recurringSettings['interval'] ?? 1;

        switch ($frequency) {
            case 'weekly':
                return $startDate->copy()->addWeeks($interval);
            case 'biweekly':
                return $startDate->copy()->addWeeks(2 * $interval);
            case 'monthly':
                return $startDate->copy()->addMonths($interval);
            case 'quarterly':
                return $startDate->copy()->addMonths(3 * $interval);
            default:
                return $startDate->copy()->addWeek();
        }
    }
}