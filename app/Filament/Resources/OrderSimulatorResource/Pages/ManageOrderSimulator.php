<?php

namespace App\Filament\Resources\OrderSimulatorResource\Pages;

use App\Filament\Resources\OrderSimulatorResource;
use App\Filament\Resources\OrderSimulatorResource\Services\OrderCalculationService;
use App\Models\Product;
use App\Models\PriceVariation;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ManageOrderSimulator extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = OrderSimulatorResource::class;

    protected static string $view = 'filament.pages.order-simulator';

    public ?array $quantities = [];

    public function mount(): void
    {
        $this->quantities = Session::get('order_simulator_quantities', []);
    }
    
    public function getTableRecordKey($record): string
    {
        return $record->composite_id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->join('product_price_variations', 'products.id', '=', 'product_price_variations.product_id')
                    ->where('products.active', true)
                    ->where('product_price_variations.is_active', true)
                    ->where('product_price_variations.pricing_type', 'retail')
                    ->where('product_price_variations.name', 'NOT LIKE', '%Wholesale%')
                    ->select([
                        DB::raw('CONCAT(products.id, "_", product_price_variations.id) as composite_id'),
                        'products.id as product_id',
                        'products.name',
                        'products.category_id',
                        'product_price_variations.id as variation_id',
                        'product_price_variations.name as variation_name',
                        'product_price_variations.fill_weight',
                        'product_price_variations.price',
                        'product_price_variations.is_default'
                    ])
                    ->with('category')
                    ->orderBy('products.name')
                    ->orderBy('product_price_variations.name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                    
                TextColumn::make('package_info')
                    ->label('Package')
                    ->getStateUsing(function ($record) {
                        $label = $record->variation_name;
                        if ($record->fill_weight) {
                            $label .= ' (' . $record->fill_weight . 'g)';
                        }
                        if ($record->is_default) {
                            $label .= ' (Default)';
                        }
                        return $label;
                    }),
                    
                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
                    
                TextInputColumn::make('quantity')
                    ->label('Quantity')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:0'])
                    ->getStateUsing(function ($record) {
                        $key = $record->composite_id;
                        return $this->quantities[$key] ?? 0;
                    })
                    ->updateStateUsing(function ($record, $state) {
                        $key = $record->composite_id;
                        if ($state > 0) {
                            $this->quantities[$key] = (int) $state;
                        } else {
                            unset($this->quantities[$key]);
                        }
                        Session::put('order_simulator_quantities', $this->quantities);
                        return $state;
                    }),
            ])
            ->headerActions([
                TableAction::make('calculate')
                    ->label('Calculate Requirements')
                    ->action('calculate')
                    ->color('success')
                    ->icon('heroicon-o-calculator'),
                    
                TableAction::make('clear')
                    ->label('Clear All')
                    ->action('clear')
                    ->color('gray')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
            ])
            ->paginated(false)
            ->searchable()
            ->striped();
    }

    public function calculate(): void
    {
        // Filter out items with zero quantity
        $activeQuantities = array_filter($this->quantities, fn($qty) => $qty > 0);
        
        if (empty($activeQuantities)) {
            Notification::make()
                ->title('No Products Selected')
                ->warning()
                ->body('Please set quantities for products you want to include in the calculation.')
                ->send();
            return;
        }
        
        // Convert to the format expected by OrderCalculationService
        $orderItems = [];
        foreach ($activeQuantities as $key => $quantity) {
            // Parse the key: "product_id_variation_id"
            $parts = explode('_', $key);
            if (count($parts) !== 2) continue;
            
            $productId = (int) $parts[0];
            $variationId = (int) $parts[1];
            
            // Verify the product and variation exist
            $product = Product::find($productId);
            $variation = PriceVariation::find($variationId);
            
            if (!$product || !$variation) continue;
            
            $orderItems[] = [
                'product_id' => $productId,
                'price_variation_id' => $variationId,
                'quantity' => $quantity
            ];
        }
        
        // Calculate requirements
        $calculator = new OrderCalculationService();
        $results = $calculator->calculateVarietyRequirements($orderItems);
        
        // Store results in session for display
        Session::put('order_simulator_results', $results);
        
        Notification::make()
            ->title('Calculation Complete')
            ->success()
            ->body('Variety requirements calculated for ' . count($orderItems) . ' product packages.')
            ->send();
        
        // Force a refresh to show results
        $this->dispatch('refresh-results');
    }

    public function clear(): void
    {
        $this->quantities = [];
        Session::forget('order_simulator_quantities');
        Session::forget('order_simulator_results');
        
        Notification::make()
            ->title('Cleared')
            ->success()
            ->body('All quantities and results have been cleared.')
            ->send();
        
        // Force a refresh to show cleared table
        $this->dispatch('refresh-results');
    }

    public function getResults(): array
    {
        return Session::get('order_simulator_results', []);
    }
}