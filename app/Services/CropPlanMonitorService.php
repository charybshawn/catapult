<?php

namespace App\Services;

use Exception;
use App\Models\CropPlan;
use App\Models\User;
use App\Notifications\CropPlanPlantingReminder;
use App\Notifications\CropPlanOverdue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural crop plan monitoring and automated notification service for microgreens production.
 * 
 * This service is the central nervous system for agricultural timing coordination in microgreens 
 * operations, ensuring that no crop plans miss their critical planting deadlines. Manages the
 * complex relationship between customer delivery commitments and agricultural growing cycles,
 * where timing precision is essential for maintaining product quality and business reliability.
 * 
 * @business_domain Agricultural production scheduling and operational alert automation
 * @agricultural_workflow Continuous monitoring of crop planting deadlines and timing constraints
 * @production_focus Microgreens cultivation with highly time-sensitive growing requirements
 * @business_impact Prevents order fulfillment failures and maintains customer delivery commitments
 * 
 * @agricultural_concepts
 * - Crop Plans: Scheduled microgreens production batches tied to specific customer orders
 * - Plant-by Dates: Critical backward-calculated deadlines from delivery date to seed germination
 * - Growing Cycles: Fixed 7-14 day agricultural timelines from seed to harvest-ready microgreens
 * - Production Windows: Narrow time constraints essential for maintaining peak freshness and quality
 * - Agricultural Workflow: Draft → Active → Planted → Growing → Harvested → Delivered progression
 * 
 * @timing_criticality
 * - Microgreens have short growing cycles making deadline adherence absolutely critical
 * - Late planting results in delayed harvests that cannot meet customer delivery commitments
 * - Agricultural timing cannot be compressed without sacrificing yield quality and safety
 * - Overdue plans create cascading delays affecting multiple customer orders
 * 
 * @notification_system
 * - Proactive Reminders: Configurable advance notice (default 2 days) for upcoming deadlines
 * - Urgent Overdue Alerts: Immediate notifications for plans past their plant-by dates
 * - Multi-Role Distribution: Growers, managers, order creators, and plan creators all notified
 * - Escalation Logic: Overdue notifications take priority over standard reminder notifications
 * 
 * @business_rules
 * - Plant-by dates calculated backward from customer delivery requirements using variety-specific growing times
 * - Only production-ready plans (draft/active status) trigger notifications to prevent spam
 * - Overdue plans threaten customer satisfaction and require immediate agricultural response
 * - Notification recipients determined by role and responsibility for comprehensive coverage
 * - Agricultural timing cannot be extended without compromising microgreens quality and safety standards
 * 
 * @agricultural_workflow_integration
 * - Supports daily production planning and resource allocation meetings
 * - Enables proactive agricultural capacity management and staff scheduling
 * - Provides data for customer communication about potential delivery delays
 * - Integrates with inventory management for seed preparation and growing space allocation
 * 
 * @performance_considerations
 * - Efficient database queries with proper eager loading to prevent N+1 issues
 * - Batch notification processing to handle high-volume production operations
 * - Error handling ensures partial failures don't stop critical notification delivery
 * - Comprehensive logging for agricultural operation troubleshooting and audit trails
 * 
 * @example
 * // Daily automated monitoring workflow
 * $monitor = new CropPlanMonitorService();
 * 
 * // Monitor upcoming planting deadlines for resource planning
 * $upcoming = $monitor->getUpcomingPlans(7); // Next 7 days of scheduled planting
 * 
 * // Execute automated notification system
 * $results = $monitor->sendPlantingReminders(2); // 2-day advance notice
 * if ($results['reminders_sent'] > 0) {
 *     Log::info("Agricultural reminders sent: {$results['reminders_sent']}");
 * }
 * 
 * // Generate production management dashboard data
 * $status = $monitor->checkPlanStatuses();
 * $summary = $monitor->getPlansSummaryByStatus();
 * 
 * // Create agricultural calendar for production planning
 * $calendar = $monitor->getPlansByPlantingDate(14);
 * 
 * @key_features
 * - Automated agricultural timing notification system with configurable advance notice
 * - Comprehensive production schedule monitoring with urgency classification
 * - Multi-stakeholder notification distribution covering all responsible agricultural roles
 * - Real-time crop plan status tracking with detailed issue identification
 * - Calendar-based agricultural workflow organization for daily operations
 * - Dashboard data generation for production management and resource allocation
 * 
 * @business_benefits
 * - Eliminates manual tracking of agricultural deadlines reducing human error
 * - Ensures consistent customer delivery commitments through automated alerts
 * - Provides operational visibility for proactive agricultural management
 * - Supports data-driven resource allocation and capacity planning decisions
 * - Maintains microgreens quality standards through precise timing coordination
 * 
 * @see CropPlan For agricultural production scheduling and deadline calculations
 * @see CropPlanPlantingReminder For advance planting deadline notification implementation
 * @see CropPlanOverdue For critical overdue production alert notifications
 * @see CropPlanningService For comprehensive production planning and workflow coordination
 * @see User For notification recipient role determination and agricultural team management
 */
class CropPlanMonitorService
{
    /**
     * Retrieve crop plans approaching critical planting deadlines for agricultural production.
     * 
     * Identifies microgreens crop plans that must be planted within the specified timeframe
     * to maintain delivery commitments. Focuses on production-ready plans (draft/active status)
     * to prevent agricultural workflow disruption and ensure fresh produce availability.
     * 
     * @business_context Essential for agricultural production planning and resource allocation
     * @agricultural_timing Critical for maintaining growing cycle timing and harvest schedules
     * @production_impact Prevents delays that would cascade through entire agricultural operation
     * 
     * @param int $days Number of days ahead to search for upcoming plant-by deadlines (default: 7)
     * @return Collection<CropPlan> Collection of crop plans requiring planting attention with relationships loaded
     * 
     * @agricultural_includes
     * - order.customer: Customer context for delivery requirements and communication
     * - recipe: Growing instructions and variety-specific agricultural parameters
     * - status: Production status for workflow filtering (draft/active only)
     * - variety: Seed variety information for agricultural planning and inventory
     * 
     * @business_rules
     * - Only includes plans with 'draft' or 'active' status (production-ready)
     * - Plant-by dates calculated backward from customer delivery requirements
     * - Excludes completed, cancelled, or paused agricultural plans
     * - Results ordered by plant_by_date for agricultural priority management
     * 
     * @example
     * // Get next week's planting schedule
     * $weeklyPlans = $monitor->getUpcomingPlans(7);
     * foreach ($weeklyPlans as $plan) {
     *     $variety = $plan->variety->name;
     *     $customer = $plan->order->customer->name;
     *     $deadline = $plan->plant_by_date->format('Y-m-d');
     * }
     */
    public function getUpcomingPlans(int $days = 7): Collection
    {
        $endDate = Carbon::now()->addDays($days)->endOfDay();
        
        return CropPlan::with(['order.customer', 'recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->where('plant_by_date', '>=', Carbon::now()->startOfDay())
            ->where('plant_by_date', '<=', $endDate)
            ->orderBy('plant_by_date')
            ->get();
    }

    /**
     * Identify overdue crop plans threatening agricultural delivery commitments.
     * 
     * Retrieves microgreens production plans that have passed their critical planting
     * deadlines, creating risk of delayed harvests and customer delivery failures.
     * These require immediate agricultural attention to minimize business impact.
     * 
     * @business_context Critical for damage control and customer communication
     * @agricultural_impact Late planting cascades to delayed harvests and delivery issues
     * @customer_service Enables proactive communication about potential delivery delays
     * 
     * @return Collection<CropPlan> Overdue production plans requiring immediate agricultural action
     * 
     * @agricultural_includes
     * - order.customer: Customer information for priority assessment and communication
     * - recipe: Production requirements to assess feasibility of recovery options
     * - status: Current workflow status for remediation planning
     * - variety: Seed variety specifics affecting replanting decisions
     * 
     * @business_rules
     * - Plant-by date has passed (earlier than current date)
     * - Only includes production-active plans (draft/active status)
     * - Ordered by plant_by_date to prioritize most critical delays
     * - Excludes plans already marked as completed or cancelled
     * 
     * @agricultural_consequences
     * - Shortened growing cycles may reduce yield quality
     * - Customer delivery dates may need adjustment
     * - Alternative variety selection might be required
     * - Additional growing space allocation may be needed
     * 
     * @example
     * // Identify production emergencies
     * $overdue = $monitor->getOverduePlans();
     * foreach ($overdue as $plan) {
     *     $daysLate = $plan->plant_by_date->diffInDays(now());
     *     // Escalate based on severity of delay
     * }
     */
    public function getOverduePlans(): Collection
    {
        return CropPlan::with(['order.customer', 'recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->where('plant_by_date', '<', Carbon::now()->startOfDay())
            ->orderBy('plant_by_date')
            ->get();
    }

    /**
     * Execute automated agricultural notification system for planting deadlines.
     * 
     * Orchestrates comprehensive reminder system for microgreens production team,
     * sending targeted notifications for upcoming planting deadlines and overdue plans.
     * Ensures agricultural workflow continuity and prevents production delays that
     * would impact customer delivery commitments.
     * 
     * @business_purpose Automates agricultural operation coordination and prevents delays
     * @agricultural_workflow Maintains growing cycle timing through proactive communication
     * @team_coordination Alerts growers, managers, and order creators simultaneously
     * 
     * @param int $daysAhead Days in advance to send planting deadline reminders (default: 2)
     * @return array Comprehensive summary of notification results and error tracking
     *   - 'reminders_sent' (int): Total notifications successfully delivered
     *   - 'errors' (array): Error messages for failed notification attempts
     * 
     * @notification_types
     * - CropPlanPlantingReminder: Advance notice for upcoming planting deadlines
     * - CropPlanOverdue: Urgent alerts for missed planting windows
     * 
     * @recipient_roles
     * - Plan creators: Original agricultural planners
     * - Order creators: Customer relationship managers
     * - Admin users: Production oversight and decision-making
     * - Grower users: Direct agricultural operation staff
     * - Manager users: Operational coordination and resource allocation
     * 
     * @business_rules
     * - Reminders sent only for production-ready plans (draft/active status)
     * - Overdue notifications prioritized over upcoming reminders
     * - Error handling ensures partial failures don't stop entire process
     * - Comprehensive logging for agricultural operation troubleshooting
     * 
     * @agricultural_timing
     * - Default 2-day advance notice allows preparation time
     * - Immediate notifications for overdue plans require urgent response
     * - Configurable timing accommodates different crop variety requirements
     * 
     * @example
     * // Execute daily notification cycle
     * $results = $monitor->sendPlantingReminders(2);
     * 
     * if ($results['reminders_sent'] > 0) {
     *     Log::info("Agricultural reminders sent: {$results['reminders_sent']}");
     * }
     * 
     * if (!empty($results['errors'])) {
     *     // Handle notification failures
     *     foreach ($results['errors'] as $error) {
     *         Log::error("Reminder notification failed: $error");
     *     }
     * }
     * 
     * @throws Exception When notification system encounters critical failures
     * @logs Agricultural notification events for operational monitoring
     */
    public function sendPlantingReminders(int $daysAhead = 2): array
    {
        $remindersSent = 0;
        $errors = [];

        try {
            // Get plans that need planting within the specified days
            $upcomingPlans = $this->getPlansNeedingReminder($daysAhead);

            foreach ($upcomingPlans as $plan) {
                try {
                    $this->sendReminderForPlan($plan);
                    $remindersSent++;
                } catch (Exception $e) {
                    $errors[] = "Failed to send reminder for plan {$plan->id}: " . $e->getMessage();
                    Log::error('Failed to send planting reminder', [
                        'crop_plan_id' => $plan->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send overdue notifications
            $overduePlans = $this->getOverduePlans();
            foreach ($overduePlans as $plan) {
                try {
                    $this->sendOverdueNotification($plan);
                    $remindersSent++;
                } catch (Exception $e) {
                    $errors[] = "Failed to send overdue notification for plan {$plan->id}: " . $e->getMessage();
                    Log::error('Failed to send overdue notification', [
                        'crop_plan_id' => $plan->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (Exception $e) {
            Log::error('Failed to process planting reminders', [
                'error' => $e->getMessage()
            ]);
            $errors[] = 'Failed to process reminders: ' . $e->getMessage();
        }

        return [
            'reminders_sent' => $remindersSent,
            'errors' => $errors
        ];
    }

    /**
     * Analyze overall agricultural production status and identify operational priorities.
     * 
     * Performs comprehensive assessment of all active microgreens crop plans,
     * categorizing by urgency level and identifying specific agricultural issues
     * requiring management attention. Provides operational dashboard data for
     * production oversight and resource allocation decisions.
     * 
     * @business_purpose Production management dashboard and operational priority identification
     * @agricultural_analysis Comprehensive crop plan health assessment and workflow monitoring
     * @management_tool Enables data-driven agricultural operation decisions
     * 
     * @return array Multi-dimensional status analysis with detailed agricultural metrics
     *   - 'overdue' (int): Plans past planting deadlines requiring immediate action
     *   - 'urgent' (int): Plans requiring planting within critical 2-day window
     *   - 'upcoming' (int): Plans scheduled for planting within next 7 days
     *   - 'on_track' (int): Plans with adequate time buffer for normal workflow
     *   - 'issues' (array): Detailed problem identification with agricultural context
     *     - 'plan_id': Unique identifier for tracking and remediation
     *     - 'order_id': Customer order context for priority assessment
     *     - 'type': Issue classification (overdue, urgent, etc.)
     *     - 'days_overdue': Quantified delay impact for decision-making
     *     - 'variety': Agricultural variety context for alternative planning
     * 
     * @agricultural_categories
     * - Overdue: Past plant-by date, threatens delivery commitments
     * - Urgent: 0-2 days until deadline, requires immediate resource allocation
     * - Upcoming: 3-7 days until deadline, normal preparation timeline
     * - On Track: 8+ days until deadline, adequate planning buffer
     * 
     * @business_rules
     * - Only analyzes production-active plans (draft/active status)
     * - Urgency calculated using model methods for consistency
     * - Issue details enable targeted remediation strategies
     * - Status counts support capacity planning and resource allocation
     * 
     * @agricultural_use_cases
     * - Daily production meetings and priority setting
     * - Resource allocation and staff scheduling
     * - Customer communication about potential delays
     * - Long-term agricultural capacity planning
     * 
     * @example
     * // Generate daily production status report
     * $status = $monitor->checkPlanStatuses();
     * 
     * echo "Production Overview:\n";
     * echo "- Overdue: {$status['overdue']} plans\n";
     * echo "- Urgent: {$status['urgent']} plans\n";
     * echo "- Upcoming: {$status['upcoming']} plans\n";
     * echo "- On Track: {$status['on_track']} plans\n";
     * 
     * if (!empty($status['issues'])) {
     *     echo "\nCritical Issues:\n";
     *     foreach ($status['issues'] as $issue) {
     *         echo "- {$issue['variety']} ({$issue['days_overdue']} days overdue)\n";
     *     }
     * }
     */
    public function checkPlanStatuses(): array
    {
        $now = Carbon::now();
        
        $statuses = [
            'overdue' => 0,
            'urgent' => 0,
            'upcoming' => 0,
            'on_track' => 0,
            'issues' => []
        ];

        $activePlans = CropPlan::whereHas('status', function ($query) {
            $query->whereIn('code', ['draft', 'active']);
        })->get();

        foreach ($activePlans as $plan) {
            if ($plan->isOverdue()) {
                $statuses['overdue']++;
                $statuses['issues'][] = [
                    'plan_id' => $plan->id,
                    'order_id' => $plan->order_id,
                    'type' => 'overdue',
                    'days_overdue' => $now->diffInDays($plan->plant_by_date),
                    'variety' => $plan->variety?->name ?? 'Unknown'
                ];
            } elseif ($plan->isUrgent()) {
                $statuses['urgent']++;
            } elseif ($plan->days_until_planting <= 7) {
                $statuses['upcoming']++;
            } else {
                $statuses['on_track']++;
            }
        }

        return $statuses;
    }

    /**
     * Identify crop plans requiring targeted planting deadline notifications.
     * 
     * Retrieves microgreens production plans that match specific notification
     * timing criteria, enabling precise agricultural workflow coordination.
     * Focuses on exact date matching to prevent notification spam while
     * ensuring critical planting deadlines receive proper attention.
     * 
     * @business_context Internal notification targeting for agricultural workflow automation
     * @agricultural_precision Exact date matching prevents premature or duplicate notifications
     * @workflow_integration Supports automated reminder system timing requirements
     * 
     * @param int $daysAhead Specific number of days ahead to target for notifications
     * @return Collection<CropPlan> Plans requiring notification on the calculated target date
     * 
     * @agricultural_includes
     * - order.customer: Customer context for notification prioritization
     * - recipe: Agricultural parameters for production planning context
     * - variety: Seed variety information for agricultural decision-making
     * 
     * @business_rules
     * - Exact date matching (whereDate) prevents notification timing drift
     * - Only production-ready plans (draft/active status) receive notifications
     * - Target date calculated from current time plus specified days ahead
     * - Eager loading optimizes database performance for notification processing
     * 
     * @protected Internal method supporting automated notification workflow
     */
    protected function getPlansNeedingReminder(int $daysAhead): Collection
    {
        $targetDate = Carbon::now()->addDays($daysAhead)->startOfDay();
        
        return CropPlan::with(['order.customer', 'recipe', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->whereDate('plant_by_date', $targetDate)
            ->get();
    }

    /**
     * Deliver targeted planting reminder to agricultural team members.
     * 
     * Distributes CropPlanPlantingReminder notifications to all relevant team
     * members associated with a specific crop plan, ensuring comprehensive
     * agricultural workflow communication and preventing production delays.
     * 
     * @business_purpose Targeted agricultural communication for specific crop plan deadlines
     * @agricultural_coordination Ensures all responsible parties receive planting alerts
     * @workflow_support Individual plan notification as part of automated reminder system
     * 
     * @param CropPlan $plan Specific agricultural crop plan requiring planting deadline notification
     * @return void Notifications sent asynchronously through Laravel notification system
     * 
     * @notification_recipients
     * - Plan creator: Original agricultural planner
     * - Order creator: Customer relationship manager
     * - Admin users: Production oversight staff
     * - Grower users: Direct agricultural operation team
     * - Manager users: Operational coordination staff
     * 
     * @business_rules
     * - Notifications sent to all identified responsible parties
     * - Uses getUsersToNotify() for consistent recipient determination
     * - Leverages Laravel notification system for delivery reliability
     * - No duplicate notification prevention (handled at higher level)
     * 
     * @protected Internal method supporting automated notification workflow
     */
    protected function sendReminderForPlan(CropPlan $plan): void
    {
        // Get users to notify
        $usersToNotify = $this->getUsersToNotify($plan);

        foreach ($usersToNotify as $user) {
            $user->notify(new CropPlanPlantingReminder($plan));
        }
    }

    /**
     * Deliver urgent overdue notification for missed planting deadline.
     * 
     * Sends CropPlanOverdue notifications to agricultural team members when
     * crop plans have passed their critical planting deadlines, enabling
     * immediate response to prevent customer delivery failures and production
     * workflow disruption.
     * 
     * @business_purpose Urgent agricultural alert for missed planting deadlines
     * @agricultural_escalation Critical notification requiring immediate team response
     * @customer_impact Prevents delivery failures through rapid remediation
     * 
     * @param CropPlan $plan Overdue crop plan threatening customer delivery commitments
     * @return void Urgent notifications sent immediately to all responsible parties
     * 
     * @notification_urgency
     * - Higher priority than standard planting reminders
     * - Requires immediate agricultural team attention
     * - May trigger alternative production planning workflows
     * - Enables proactive customer communication about delays
     * 
     * @business_rules
     * - Sent only for plans past their plant_by_date deadline
     * - Uses same recipient logic as standard reminders for consistency
     * - Leverages Laravel notification system for reliable delivery
     * - No built-in frequency limiting (managed at calling level)
     * 
     * @protected Internal method supporting overdue notification workflow
     */
    protected function sendOverdueNotification(CropPlan $plan): void
    {
        // Get users to notify
        $usersToNotify = $this->getUsersToNotify($plan);

        foreach ($usersToNotify as $user) {
            $user->notify(new CropPlanOverdue($plan));
        }
    }

    /**
     * Determine comprehensive recipient list for crop plan notifications.
     * 
     * Identifies all team members who should receive agricultural notifications
     * about a specific crop plan, including original planners, customer managers,
     * and operational staff. Ensures comprehensive communication while preventing
     * notification spam through intelligent role-based targeting.
     * 
     * @business_purpose Comprehensive agricultural team communication strategy
     * @notification_targeting Role-based recipient determination for operational efficiency
     * @team_coordination Ensures all responsible parties stay informed about production status
     * 
     * @param CropPlan $plan Crop plan requiring team notification about status or deadlines
     * @return Collection<User> Unique list of users requiring notification about this plan
     * 
     * @recipient_categories
     * - Plan Creator: User who originally created the agricultural crop plan
     * - Order Creator: User who created the associated customer order
     * - Operational Roles: Admin, grower, and manager users with agricultural responsibilities
     * 
     * @business_rules
     * - Automatic deduplication prevents multiple notifications to same user
     * - Role-based inclusion ensures operational coverage
     * - Creator inclusion maintains accountability and awareness
     * - Empty relationships handled gracefully (null-safe)
     * 
     * @agricultural_roles
     * - Admin: Full production oversight and decision-making authority
     * - Grower: Direct agricultural operation and crop management
     * - Manager: Operational coordination and resource allocation
     * 
     * @example
     * // Get notification recipients for specific plan
     * $recipients = $this->getUsersToNotify($cropPlan);
     * foreach ($recipients as $user) {
     *     // Send notification to each unique recipient
     *     $user->notify(new CropPlanPlantingReminder($cropPlan));
     * }
     * 
     * @protected Internal method supporting notification distribution logic
     */
    protected function getUsersToNotify(CropPlan $plan): Collection
    {
        $users = collect();

        // Add plan creator
        if ($plan->createdBy) {
            $users->push($plan->createdBy);
        }

        // Add order creator
        if ($plan->order && $plan->order->user) {
            $users->push($plan->order->user);
        }

        // Add users with grower role
        $growers = User::role(['admin', 'grower', 'manager'])->get();
        $users = $users->merge($growers);

        return $users->unique('id');
    }

    /**
     * Generate comprehensive agricultural production status summary for dashboard display.
     * 
     * Creates detailed status breakdown of all microgreens crop plans organized by
     * workflow status, urgency level, and operational priority. Provides essential
     * data for production management dashboards, resource allocation decisions,
     * and agricultural operation oversight.
     * 
     * @business_purpose Production dashboard data and operational management metrics
     * @agricultural_overview Complete production status visibility for decision-making
     * @management_tool Enables data-driven agricultural capacity and priority planning
     * 
     * @return array Multi-dimensional status summary with agricultural business context
     *   - Status categories with count, name, and visual color coding
     *   - 'overdue': Critical plans past planting deadlines (danger color)
     *   - 'urgent': Plans requiring planting within 2 days (warning color)
     *   - Standard workflow statuses: draft, active, completed, etc. with configured colors
     * 
     * @dashboard_metrics
     * - Count: Quantified plan numbers for capacity assessment
     * - Name: User-friendly status descriptions for dashboard display
     * - Color: Visual indicators for quick operational priority identification
     * 
     * @agricultural_categories
     * - Standard Status: Normal workflow progression (draft, active, completed)
     * - Overdue Status: Past plant-by date, requires immediate agricultural attention
     * - Urgent Status: 0-2 days until deadline, needs priority resource allocation
     * 
     * @business_rules
     * - Overdue count calculated from actual database query results
     * - Urgent count uses date range filtering for 2-day window
     * - Standard statuses pulled from database with configured colors
     * - Status codes used as array keys for programmatic access
     * 
     * @agricultural_use_cases
     * - Production management dashboard visualization
     * - Daily operational priority assessment
     * - Resource allocation and staff scheduling
     * - Agricultural capacity planning and forecasting
     * 
     * @example
     * // Generate dashboard status summary
     * $summary = $monitor->getPlansSummaryByStatus();
     * 
     * foreach ($summary as $status => $data) {
     *     echo "{$data['name']}: {$data['count']} plans\n";
     *     // Use $data['color'] for dashboard styling
     * }
     * 
     * // Check for critical agricultural issues
     * if ($summary['overdue']['count'] > 0) {
     *     // Escalate overdue production plans
     * }
     */
    public function getPlansSummaryByStatus(): array
    {
        $summary = [];

        // Get counts by status
        $statusCounts = CropPlan::selectRaw('status_id, count(*) as count')
            ->with('status')
            ->groupBy('status_id')
            ->get();

        foreach ($statusCounts as $statusCount) {
            $summary[$statusCount->status->code] = [
                'name' => $statusCount->status->name,
                'count' => $statusCount->count,
                'color' => $statusCount->status->color
            ];
        }

        // Add overdue and urgent counts
        $overduePlans = $this->getOverduePlans();
        $summary['overdue'] = [
            'name' => 'Overdue',
            'count' => $overduePlans->count(),
            'color' => 'danger'
        ];

        $urgentPlans = CropPlan::whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->whereBetween('plant_by_date', [
                Carbon::now()->startOfDay(),
                Carbon::now()->addDays(2)->endOfDay()
            ])
            ->count();

        $summary['urgent'] = [
            'name' => 'Urgent (Next 2 Days)',
            'count' => $urgentPlans,
            'color' => 'warning'
        ];

        return $summary;
    }

    /**
     * Organize crop plans by planting date for agricultural calendar visualization.
     * 
     * Groups upcoming microgreens production plans by their specific planting dates,
     * enabling calendar-based production scheduling and agricultural resource planning.
     * Provides structured data for timeline visualizations and daily operational
     * planning workflows.
     * 
     * @business_purpose Agricultural calendar organization and daily production planning
     * @agricultural_scheduling Enables date-based production workflow coordination
     * @resource_planning Supports daily agricultural resource allocation and staff scheduling
     * 
     * @param int $days Number of days ahead to include in agricultural calendar (default: 14)
     * @return Collection Nested collection grouped by planting date strings (Y-m-d format)
     *   - Keys: Date strings in 'Y-m-d' format for calendar integration
     *   - Values: Collections of CropPlan objects for each specific date
     * 
     * @agricultural_includes
     * - order.customer: Customer context for production priority and communication
     * - recipe: Growing instructions and agricultural parameters
     * - status: Production workflow status for operational filtering
     * - variety: Seed variety information for resource planning and inventory
     * 
     * @business_rules
     * - Only includes production-ready plans (draft/active status)
     * - Date range from current day through specified days ahead
     * - Results ordered chronologically by plant_by_date
     * - Grouped by exact date for calendar day organization
     * 
     * @agricultural_use_cases
     * - Daily production schedule generation
     * - Agricultural calendar widget data
     * - Resource planning and staff allocation
     * - Seed inventory preparation workflows
     * 
     * @calendar_integration
     * - Date keys compatible with JavaScript Date() constructor
     * - Y-m-d format standard for agricultural scheduling systems
     * - Nested structure supports daily detail expansion
     * - Empty dates not included (sparse collection)
     * 
     * @example
     * // Generate 2-week agricultural calendar
     * $calendar = $monitor->getPlansByPlantingDate(14);
     * 
     * foreach ($calendar as $date => $plans) {
     *     echo "$date: " . $plans->count() . " plans to plant\n";
     *     foreach ($plans as $plan) {
     *         echo "- {$plan->variety->name} for {$plan->order->customer->name}\n";
     *     }
     * }
     * 
     * // Check specific date workload
     * $tomorrow = now()->addDay()->format('Y-m-d');
     * if (isset($calendar[$tomorrow])) {
     *     $workload = $calendar[$tomorrow]->count();
     *     // Plan agricultural resources accordingly
     * }
     */
    public function getPlansByPlantingDate(int $days = 14): Collection
    {
        $endDate = Carbon::now()->addDays($days)->endOfDay();
        
        $plans = CropPlan::with(['order.customer', 'recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->where('plant_by_date', '>=', Carbon::now()->startOfDay())
            ->where('plant_by_date', '<=', $endDate)
            ->orderBy('plant_by_date')
            ->get();

        return $plans->groupBy(function ($plan) {
            return $plan->plant_by_date->format('Y-m-d');
        });
    }
}