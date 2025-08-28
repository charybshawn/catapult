<?php

namespace App\Filament\Pages;

use Filament\Panel;
use Filament\Schemas\Schema;
use App\Models\CropStage;
use App\Models\Crop;
use App\Models\Order;
use App\Models\Recipe;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Weekly Production Planning Dashboard for agricultural microgreens operations.
 * 
 * Provides comprehensive weekly production planning functionality that aligns crop
 * planting schedules with customer order fulfillment requirements. Calculates optimal
 * planting timing, tray requirements, and production capacity to ensure on-time
 * delivery while maximizing resource utilization.
 *
 * @package App\Filament\Pages
 * @uses CropStage For harvest stage filtering and crop lifecycle management
 * @uses Order For harvest date coordination and customer demand analysis
 * @uses Recipe For production timing calculations and yield expectations
 * @uses Carbon For precise date calculations and week-based planning
 * 
 * **Business Context:**
 * - **Production Planning**: Coordinates crop planting with delivery commitments
 * - **Capacity Management**: Calculates tray requirements vs. available production
 * - **Customer Fulfillment**: Ensures adequate supply for confirmed orders
 * - **Resource Optimization**: Minimizes waste through precise production planning
 * 
 * **Agricultural Workflow:**
 * - Wednesday-centered harvest schedule (industry standard for microgreens)
 * - Backward planning from harvest date to determine planting requirements
 * - Recipe-based timing calculations for different crop varieties
 * - Existing crop inventory consideration to avoid overproduction
 * 
 * **Key Features:**
 * 1. **Week Selection**: Interactive date picker for planning different weeks
 * 2. **Harvest Aggregation**: Summarizes all orders by product for total demand
 * 3. **Planting Recommendations**: Calculates required planting based on recipes
 * 4. **Capacity Analysis**: Compares required vs. existing production capacity
 * 5. **Timeline Visualization**: Shows planting dates relative to harvest timing
 */
class WeeklyPlanning extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Weekly Planning';

    protected static ?string $title = 'Weekly Planning';

    protected static bool $shouldRegisterNavigation = false;

    public static function getSlug(?Panel $panel = null): string
    {
        return static::$slug ?? 'weekly-planning';
    }

    protected string $view = 'filament.pages.weekly-planning';

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    // Specify the panel this page belongs to
    public static function getActiveNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar';
    }

    /**
     * Currently selected date for weekly planning analysis.
     * 
     * @var string|null ISO date string representing the selected planning week
     * @livewire_property Reactive property that triggers data recalculation
     */
    public $selectedDate;

    /**
     * Initialize weekly planning page with current week as default.
     * 
     * Sets up the initial form state with today's date and initializes the
     * selectedDate property for weekly planning calculations. The form system
     * handles reactive updates when the user changes the selected date.
     * 
     * @filament_lifecycle Standard Filament page initialization
     * @default_behavior Starts with current week for immediate relevance
     */
    public function mount(): void
    {
        $this->form->fill([
            'selectedDate' => Carbon::now()->toDateString(),
        ]);

        $this->selectedDate = Carbon::now()->toDateString();
    }

    /**
     * Build the interactive form schema for weekly planning controls.
     * 
     * Creates a reactive date picker that allows users to navigate between different
     * weeks for production planning. The live updating ensures immediate recalculation
     * of planting recommendations and harvest aggregations when the date changes.
     * 
     * @param Schema $schema Filament form schema builder
     * @return Schema Configured form with reactive date picker
     * 
     * @filament_forms Live updating form for immediate planning feedback
     * @ui_interaction Triggers complete data refresh on date selection
     * @planning_workflow Central control for week-based analysis
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('selectedDate')
                    ->label('Select Week')
                    ->default(Carbon::now()->toDateString())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDate = $state;
                    }),
            ]);
    }

    /**
     * Compile comprehensive weekly planning data for agricultural operations.
     * 
     * Generates complete dataset for weekly production planning including harvest
     * demand aggregation, active crop inventory, and calculated planting recommendations.
     * Uses Wednesday-centered harvest scheduling (industry standard for microgreens)
     * and performs backward planning calculations to determine optimal planting timing.
     * 
     * **Data Processing Workflow:**
     * 1. Calculate Wednesday harvest date for selected week
     * 2. Aggregate all customer orders for harvest date
     * 3. Summarize product quantities across all orders
     * 4. Inventory current active crops approaching harvest
     * 5. Calculate planting recommendations based on demand vs. capacity
     * 
     * @return array Complete planning dataset for template rendering
     * 
     * **Return Structure:**
     * - harvests: Individual orders for the week
     * - productTotals: Aggregated demand by product with customer breakdown
     * - activeCrops: Current production inventory and stage status
     * - plantingRecommendations: Required planting with timing and capacity analysis
     * 
     * @agricultural_planning Central data compilation for production coordination
     * @business_intelligence Provides complete picture for resource allocation
     * @capacity_planning Balances demand forecasting with production capability
     */
    public function getViewData(): array
    {
        $selectedDate = $this->selectedDate ? Carbon::parse($this->selectedDate) : Carbon::now();

        // Get Wednesday of the selected week
        $wednesday = $selectedDate->copy()->startOfWeek()->addDays(3);

        // Get orders for this week's harvest (Wednesday)
        $harvests = Order::where('harvest_date', $wednesday->toDateString())
            ->with(['orderItems.item', 'crops', 'user'])
            ->get();

        // Calculate aggregate quantities for each product
        $productTotals = [];
        foreach ($harvests as $order) {
            foreach ($order->orderItems as $item) {
                $productName = $item->item->name;
                if (! isset($productTotals[$productName])) {
                    $productTotals[$productName] = [
                        'item' => $item->item,
                        'quantity' => 0,
                        'orders' => [],
                    ];
                }
                $productTotals[$productName]['quantity'] += $item->quantity;
                $productTotals[$productName]['orders'][] = [
                    'order_id' => $order->id,
                    'customer' => $order->user->name,
                    'quantity' => $item->quantity,
                ];
            }
        }

        // Get active crops (not harvested)
        $harvestedStage = CropStage::findByCode('harvested');
        $activeCrops = Crop::where('current_stage_id', '!=', $harvestedStage?->id)
            ->with(['recipe', 'order.user', 'currentStage'])
            ->orderBy('germination_at', 'desc')
            ->get();

        // Calculate planting recommendations
        $plantingRecommendations = $this->calculatePlantingRecommendations($harvests, $wednesday);

        return [
            'harvests' => $harvests,
            'productTotals' => $productTotals,
            'activeCrops' => $activeCrops,
            'selectedDate' => $selectedDate,
            'harvestDate' => $wednesday,
            'plantingRecommendations' => $plantingRecommendations,
        ];
    }

    /**
     * Calculate sophisticated planting recommendations based on harvest demand analysis.
     * 
     * Performs comprehensive backward planning calculations to determine optimal planting
     * schedules that will meet customer order requirements. Analyzes existing crop
     * inventory, calculates required production capacity, and recommends additional
     * planting needs with precise timing based on recipe-specific growth durations.
     * 
     * **Calculation Process:**
     * 1. **Demand Aggregation**: Summarizes all product quantities by recipe
     * 2. **Timing Calculation**: Uses recipe totalDays() for backward planning
     * 3. **Capacity Analysis**: Calculates required trays based on yield expectations
     * 4. **Inventory Assessment**: Evaluates existing crops that will mature on time
     * 5. **Gap Analysis**: Determines additional planting requirements
     * 
     * **Agricultural Logic:**
     * - Groups products by recipe_id for consistent growth characteristics
     * - Calculates plant_by_date using recipe-specific growth duration
     * - Converts product quantities to tray requirements using yield estimates
     * - Accounts for existing production to avoid overplanting
     * 
     * @param Collection $orders Customer orders for the target harvest date
     * @param Carbon $harvestDate Target harvest/delivery date for planning
     * @return array Comprehensive planting recommendations with capacity analysis
     * 
     * **Return Data Structure:**
     * - recipe: Recipe model for growth characteristics
     * - quantity: Total product demand across all orders
     * - total_days: Growth duration from planting to harvest
     * - plant_by_date: Latest date to plant for on-time harvest
     * - trays_needed: Total tray capacity required for demand
     * - existing_trays: Current inventory that will mature on time
     * - additional_trays_needed: Gap requiring new planting
     * 
     * @agricultural_planning Core calculation for production capacity planning
     * @business_optimization Prevents overproduction while ensuring adequate supply
     * @timing_critical Accurate timing calculations prevent delivery delays
     */
    protected function calculatePlantingRecommendations(Collection $orders, Carbon $harvestDate): array
    {
        $recommendations = [];

        // Group all products and their quantities
        $productQuantities = [];
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $productName = $item->item->name;
                $recipeId = $item->item->recipe_id;

                if (! isset($productQuantities[$recipeId])) {
                    $recipe = Recipe::find($recipeId);
                    $totalDays = $recipe ? $recipe->totalDays() : 10; // Default to 10 days if recipe not found

                    $productQuantities[$recipeId] = [
                        'recipe' => $recipe,
                        'name' => $productName,
                        'quantity' => 0,
                        'total_days' => $totalDays,
                        'plant_by_date' => $harvestDate->copy()->subDays($totalDays),
                        'item' => $item->item,
                    ];
                }

                $productQuantities[$recipeId]['quantity'] += $item->quantity;
            }
        }

        // Calculate recommended trays
        foreach ($productQuantities as $recipeId => $data) {
            $recipe = $data['recipe'];
            if (! $recipe) {
                continue;
            }

            $quantityNeeded = $data['quantity']; // Number of units ordered
            $expectedYieldPerTray = $data['item']->expected_yield_grams;
            $traysNeeded = 0;

            if ($expectedYieldPerTray > 0) {
                $traysNeeded = ceil($quantityNeeded / ($expectedYieldPerTray / 1000)); // Convert grams to kg
            }

            $productQuantities[$recipeId]['trays_needed'] = $traysNeeded;

            // Check existing crops for this recipe that will be ready by harvest date
            $harvestedStage = CropStage::findByCode('harvested');
            $existingTrays = Crop::where('recipe_id', $recipeId)
                ->where('current_stage_id', '!=', $harvestedStage?->id)
                ->get()
                ->filter(function ($crop) use ($harvestDate) {
                    $expectedHarvest = $crop->expectedHarvestDate();

                    return $expectedHarvest && $expectedHarvest->isSameDay($harvestDate);
                })
                ->count();

            $productQuantities[$recipeId]['existing_trays'] = $existingTrays;
            $productQuantities[$recipeId]['additional_trays_needed'] = max(0, $traysNeeded - $existingTrays);
        }

        return $productQuantities;
    }
}
