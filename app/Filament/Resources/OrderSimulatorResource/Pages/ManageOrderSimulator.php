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
use Filament\Tables\Columns\IconColumn;
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
    public ?array $hiddenRows = [];
    public bool $showHiddenPanel = false;

    public function mount(): void
    {
        $this->quantities = Session::get('order_simulator_quantities', []);
        $this->hiddenRows = Session::get('order_simulator_hidden_rows', []);
        $this->showHiddenPanel = Session::get('order_simulator_panel_open', false);
    }
    
    public function getTableRecordKey($record): string
    {
        // Build composite key from individual IDs to ensure consistency
        // Must match the format used in hideRow() and quantity tracking
        return (string)$record->product_id . '_' . (string)$record->variation_id;
    }

    public function table(Table $table): Table
    {
        $baseQuery = Product::query()
            ->join('product_price_variations', 'products.id', '=', 'product_price_variations.product_id')
            ->where('products.active', true)
            ->where('product_price_variations.is_active', true)
            ->where('product_price_variations.pricing_type', 'retail')
            ->where('product_price_variations.name', 'NOT LIKE', '%Wholesale%')
            ->where('product_price_variations.name', 'NOT LIKE', '%Live Tray%')
            ->where('product_price_variations.name', 'NOT LIKE', '%live tray%')
            ->select([
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
            ->orderBy('products.id')
            ->orderBy('product_price_variations.id');

        // Filter out hidden rows if any exist
        if (!empty($this->hiddenRows)) {
            $baseQuery->whereNotIn(
                DB::raw('CONCAT(products.id, "_", product_price_variations.id)'),
                array_keys($this->hiddenRows)
            );
        }

        return $table
            ->query($baseQuery)
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
                        $key = $record->product_id . '_' . $record->variation_id;
                        return $this->quantities[$key] ?? 0;
                    })
                    ->updateStateUsing(function ($record, $state) {
                        $key = $record->product_id . '_' . $record->variation_id;
                        if ($state > 0) {
                            $this->quantities[$key] = (int) $state;
                        } else {
                            unset($this->quantities[$key]);
                        }
                        Session::put('order_simulator_quantities', $this->quantities);
                        return $state;
                    }),
            ])
            ->actions([
                TableAction::make('hide')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->action(function ($record) {
                        // Get fresh data from database to ensure accuracy
                        $freshRecord = Product::query()
                            ->join('product_price_variations', 'products.id', '=', 'product_price_variations.product_id')
                            ->where('products.id', $record->product_id)
                            ->where('product_price_variations.id', $record->variation_id)
                            ->select([
                                'products.id as product_id',
                                'products.name',
                                'product_price_variations.id as variation_id',
                                'product_price_variations.name as variation_name',
                            ])
                            ->first();
                        
                        if (!$freshRecord) {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('Could not find the specified product variation.')
                                ->send();
                            return;
                        }
                        
                        $compositeId = $freshRecord->product_id . '_' . $freshRecord->variation_id;
                        $this->hideRow($compositeId);
                    })
                    ->tooltip('Hide this row from the list'),
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
                    ->requiresConfirmation(),
                    
                TableAction::make('show_hidden')
                    ->label(function () {
                        $hiddenCount = count($this->hiddenRows);
                        return "Show Hidden ({$hiddenCount})";
                    })
                    ->action('toggleHiddenPanel')
                    ->color('warning')
                    ->icon('heroicon-o-eye-slash')
                    ->visible(fn() => count($this->hiddenRows) > 0)
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
        $this->hiddenRows = [];
        $this->showHiddenPanel = false;
        Session::forget('order_simulator_quantities');
        Session::forget('order_simulator_hidden_rows');
        Session::forget('order_simulator_results');
        Session::forget('order_simulator_panel_open');
        
        Notification::make()
            ->title('Cleared')
            ->success()
            ->body('All quantities, hidden rows, and results have been cleared.')
            ->send();
        
        // Force a refresh to show cleared table
        $this->dispatch('refresh-results');
        $this->dispatch('$refresh');
    }

    public function hideRow(string $compositeId): void
    {
        // Get the product name for the notification
        $parts = explode('_', $compositeId);
        if (count($parts) !== 2) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Invalid row identifier format.')
                ->send();
            return;
        }
        
        $productId = (int) $parts[0];
        $variationId = (int) $parts[1];
        
        $product = Product::find($productId);
        $variation = PriceVariation::find($variationId);
        
        if (!$product || !$variation) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Could not find the specified product or variation.')
                ->send();
            return;
        }
        
        $rowName = $product->name;
        if ($variation) {
            $rowName .= ' - ' . $variation->name;
        }
        
        // Add to hidden rows
        $this->hiddenRows[$compositeId] = [
            'product_name' => $rowName,
            'hidden_at' => now()->toISOString(),
        ];
        
        // Remove from quantities if it was set
        unset($this->quantities[$compositeId]);
        
        // Update session
        Session::put('order_simulator_hidden_rows', $this->hiddenRows);
        Session::put('order_simulator_quantities', $this->quantities);
        
        Notification::make()
            ->title('Row Hidden')
            ->success()
            ->body("'{$rowName}' has been hidden from the list. (ID: {$compositeId})")
            ->send();
        
        // Refresh the table to reflect changes
        $this->dispatch('$refresh');
    }

    public function showHiddenRows(): void
    {
        if (empty($this->hiddenRows)) {
            return;
        }
        
        $hiddenCount = count($this->hiddenRows);
        
        // Clear all hidden rows
        $this->hiddenRows = [];
        $this->showHiddenPanel = false;
        
        Session::forget('order_simulator_hidden_rows');
        Session::put('order_simulator_panel_open', $this->showHiddenPanel);
        
        Notification::make()
            ->title('Rows Restored')
            ->success()
            ->body("{$hiddenCount} hidden rows have been restored to the list.")
            ->send();
        
        // Refresh the table to show all rows again
        $this->dispatch('$refresh');
    }

    public function showHiddenItem(string $compositeId): void
    {
        if (!isset($this->hiddenRows[$compositeId])) {
            return;
        }
        
        $rowName = $this->hiddenRows[$compositeId]['product_name'];
        
        // Remove from hidden rows
        unset($this->hiddenRows[$compositeId]);
        
        // Hide panel if no more hidden rows
        if (empty($this->hiddenRows)) {
            $this->showHiddenPanel = false;
        }
        
        // Update session
        Session::put('order_simulator_hidden_rows', $this->hiddenRows);
        Session::put('order_simulator_panel_open', $this->showHiddenPanel);
        
        Notification::make()
            ->title('Row Restored')
            ->success()
            ->body("'{$rowName}' has been restored to the list.")
            ->send();
        
        // Refresh the table to show the restored row
        $this->dispatch('$refresh');
    }

    public function toggleHiddenPanel(): void
    {
        $this->showHiddenPanel = !$this->showHiddenPanel;
        Session::put('order_simulator_panel_open', $this->showHiddenPanel);
    }

    public function getResults(): array
    {
        return Session::get('order_simulator_results', []);
    }

    public function getHeaderActions(): array
    {
        $results = $this->getResults();
        
        if (empty($results)) {
            return [];
        }

        return [
            TableAction::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->action(function () {
                    $this->printResults();
                })
                ->tooltip('Print calculation results'),
        ];
    }


    protected function printResults()
    {
        $results = $this->getResults();
        
        if (empty($results)) {
            Notification::make()
                ->title('No Results')
                ->warning()
                ->body('No calculation results available to print.')
                ->send();
            return;
        }

        // Dispatch event to open print dialog in JavaScript
        $this->dispatch('open-print-dialog', [
            'results' => $results,
            'generated_at' => now()->toISOString(),
            'total_varieties' => $results['summary']['total_varieties'] ?? 0,
            'total_items' => $results['summary']['total_items'] ?? 0,
            'total_grams' => $results['summary']['total_grams'] ?? 0,
        ]);
    }
}