<?php

namespace App\Filament\Pages;

use App\Models\Crop;
use App\Models\Item;
use App\Models\Order;
use App\Models\Recipe;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;

class WeeklyPlanning extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Weekly Planning';
    protected static ?string $title = 'Weekly Planning';
    protected static bool $shouldRegisterNavigation = false;
    
    public static function getSlug(): string
    {
        return static::$slug ?? 'weekly-planning';
    }
    
    protected static string $view = 'filament.pages.weekly-planning';
    
    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
    
    // Specify the panel this page belongs to
    public static function getActiveNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar';
    }
    
    public $selectedDate;
    
    public function mount(): void
    {
        $this->form->fill([
            'selectedDate' => Carbon::now()->toDateString(),
        ]);
        
        $this->selectedDate = Carbon::now()->toDateString();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('selectedDate')
                    ->label('Select Week')
                    ->default(Carbon::now()->toDateString())
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDate = $state;
                    }),
            ]);
    }
    
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
                if (!isset($productTotals[$productName])) {
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
        $activeCrops = Crop::whereNotIn('current_stage', ['harvested'])
            ->with(['recipe', 'order.user'])
            ->orderBy('planting_at', 'desc')
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
    
    protected function calculatePlantingRecommendations(Collection $orders, Carbon $harvestDate): array
    {
        $recommendations = [];
        
        // Group all products and their quantities
        $productQuantities = [];
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $productName = $item->item->name;
                $recipeId = $item->item->recipe_id;
                
                if (!isset($productQuantities[$recipeId])) {
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
            if (!$recipe) continue;
            
            $quantityNeeded = $data['quantity']; // Number of units ordered
            $expectedYieldPerTray = $data['item']->expected_yield_grams;
            $traysNeeded = 0;
            
            if ($expectedYieldPerTray > 0) {
                $traysNeeded = ceil($quantityNeeded / ($expectedYieldPerTray / 1000)); // Convert grams to kg
            }
            
            $productQuantities[$recipeId]['trays_needed'] = $traysNeeded;
            
            // Check existing crops for this recipe that will be ready by harvest date
            $existingTrays = Crop::where('recipe_id', $recipeId)
                ->whereNotIn('current_stage', ['harvested'])
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