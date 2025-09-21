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
use Filament\Actions\Action as PageAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ManageOrderSimulator extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = OrderSimulatorResource::class;

    protected static string $view = 'filament.pages.order-simulator';

    public array $quantities = [];
    public array $hiddenRows = [];
    public bool $showHiddenPanel = false;

    public function mount(): void
    {
        $this->quantities = Session::get('order_simulator_quantities', []);
        $this->hiddenRows = Session::get('order_simulator_hidden_rows', []);
        $this->showHiddenPanel = Session::get('order_simulator_panel_open', false);
    }
    
    public function updatedQuantities($value, $key): void
    {
        // Save to session whenever quantities are updated
        Session::put('order_simulator_quantities', $this->quantities);
    }
    
    public function getTableRecordKey($record): string
    {
        // Use variation_id directly as the record key
        return (string) $record->variation_id;
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
                'product_price_variations.fill_weight_grams',
                'product_price_variations.price',
                'product_price_variations.is_default'
            ])
            ->with('category')
            // CRITICAL FIX: Use deterministic ordering with primary keys first
            // This ensures consistent record positioning across page loads
            ->orderBy('products.id')
            ->orderBy('product_price_variations.id')
            ->orderBy('products.name')
            ->orderBy('product_price_variations.name');

        // Filter out hidden rows if any exist
        if (!empty($this->hiddenRows)) {
            // Use variation IDs directly as keys
            $hiddenVariationIds = array_keys($this->hiddenRows);
            $baseQuery->whereNotIn('product_price_variations.id', $hiddenVariationIds);
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
                        return preg_replace('/\s*-\s*\$[\d,]+\.?\d*/', '', $record->variation_name);
                    }),

                TextColumn::make('fill_weight_grams')
                    ->label('Fill Weight')
                    ->getStateUsing(function ($record) {
                        return $record->fill_weight_grams ? $record->fill_weight_grams . 'g' : 'N/A';
                    }),

                ViewColumn::make('quantity')
                    ->label('Quantity')
                    ->view('filament.tables.columns.quantity-input'),
            ])
            ->actions([
                TableAction::make('hide')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->action(function ($record) {
                        // Use variation_id directly as the key
                        $this->hideRow((string) $record->variation_id);
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
        foreach ($activeQuantities as $variationIdKey => $quantity) {
            // Use variation ID directly as it's already the key
            $variationId = (int) $variationIdKey;
            
            // Get the variation and its product
            $variation = PriceVariation::with('product')->find($variationId);
            if (!$variation || !$variation->product) continue;
            
            $orderItems[] = [
                'product_id' => $variation->product_id,
                'price_variation_id' => $variationId,
                'quantity' => $quantity
            ];
        }
        
        // Calculate requirements
        $calculator = new OrderCalculationService();
        $results = $calculator->calculateVarietyRequirements($orderItems);
        
        // Store results in session for display
        Session::put('order_simulator_results', $results);
        
        // Check for errors and notify user appropriately
        if (!empty($results['missing_fill_weights'])) {
            $missingProducts = collect($results['missing_fill_weights'])
                ->map(fn($item) => "• {$item['product_name']} - {$item['variation_name']}")
                ->join("\n");
            
            Notification::make()
                ->title('Missing Fill Weight Data')
                ->warning()
                ->body("The following products are missing fill weight data and were skipped:\n\n{$missingProducts}\n\nPlease update the fill weight (grams) for these product variations to include them in calculations.")
                ->duration(10000) // Show for 10 seconds
                ->send();
        }
        
        // Show success notification if any items were calculated
        if (!empty($results['variety_totals']) || !empty($results['item_breakdown'])) {
            $successMessage = 'Variety requirements calculated for ' . count($results['item_breakdown']) . ' product packages.';
            if (!empty($results['missing_fill_weights'])) {
                $successMessage .= ' (' . count($results['missing_fill_weights']) . ' items skipped due to missing data)';
            }
            
            Notification::make()
                ->title('Calculation Complete')
                ->success()
                ->body($successMessage)
                ->send();
        }
        
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

    public function hideRow(string $variationId): void
    {
        // Get the variation and its product
        $variation = PriceVariation::with('product')->find($variationId);
        
        if (!$variation || !$variation->product) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Could not find the specified product variation.')
                ->send();
            return;
        }
        
        $rowName = $variation->product->name . ' - ' . $variation->name;
        
        // Add to hidden rows using variation_id as key
        $this->hiddenRows[$variationId] = [
            'product_name' => $rowName,
            'hidden_at' => now()->toISOString(),
        ];
        
        // Remove from quantities if it was set using variation_id as key
        unset($this->quantities[$variationId]);
        
        // Update session
        Session::put('order_simulator_hidden_rows', $this->hiddenRows);
        Session::put('order_simulator_quantities', $this->quantities);
        
        Notification::make()
            ->title('Row Hidden')
            ->success()
            ->body("'{$rowName}' has been hidden from the list. (ID: {$variationId})")
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

    public function showHiddenItem(string $variationId): void
    {
        if (!isset($this->hiddenRows[$variationId])) {
            return;
        }
        
        $rowName = $this->hiddenRows[$variationId]['product_name'];
        
        // Remove from hidden rows
        unset($this->hiddenRows[$variationId]);
        
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
            PageAction::make('print')
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

        // Generate the print data
        $printData = [
            'results' => $results,
            'generated_at' => now()->toISOString(),
            'total_varieties' => $results['summary']['total_varieties'] ?? 0,
            'total_items' => $results['summary']['total_items'] ?? 0,
            'total_grams' => $results['summary']['total_grams'] ?? 0,
        ];
        
        // Call the print function
        $escapedData = addslashes(json_encode($printData));
        $this->js("
            if (typeof window.openPrintWindow === 'function') {
                const data = JSON.parse('{$escapedData}');
                window.openPrintWindow(data);
            } else {
                console.error('openPrintWindow function not found');
                alert('Print function not available');
            }
        ");
    }
}