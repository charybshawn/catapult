<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SeedEntry;
use App\Services\CropPlanCalculatorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManualCropPlanning extends Page
{
    protected static string $resource = CropPlanResource::class;
    
    protected static string $view = 'filament.resources.crop-plan-resource.pages.manual-crop-planning';
    
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $title = 'Manual Crop Planning';
    
    public ?array $data = [];
    
    public ?string $delivery_date = null;
    public ?Collection $orders = null;
    public ?array $plantingPlan = null;
    public ?array $calculationDetails = null;

    public function mount(): void
    {
        $this->form->fill([
            'delivery_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Select Delivery Date')
                    ->schema([
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required()
                            ->default(now()->addDays(7))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->loadOrdersForDate()),
                    ])
            ])
            ->statePath('data');
    }

    public function loadOrdersForDate(): void
    {
        if (!$this->data['delivery_date']) {
            $this->orders = collect();
            $this->plantingPlan = null;
            return;
        }

        $this->orders = Order::with([
            'customer',
            'orderItems.product.productMix.seedEntries',
            'orderItems.priceVariation.packagingType'
        ])
            ->where('delivery_date', $this->data['delivery_date'])
            ->where('status', '!=', 'cancelled')
            ->get();

        $this->calculatePlantingPlan();
    }

    public function calculatePlantingPlan(): void
    {
        if (!$this->orders || $this->orders->isEmpty()) {
            $this->plantingPlan = null;
            $this->calculationDetails = null;
            return;
        }

        $planCalculator = app(CropPlanCalculatorService::class);
        $result = $planCalculator->calculateForOrders($this->orders);
        
        $this->plantingPlan = $result['planting_plan'];
        $this->calculationDetails = $result['calculation_details'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->loadOrdersForDate()),
                
            Action::make('generate_pdf')
                ->label('Generate PDF Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn () => !empty($this->plantingPlan))
                ->url(fn () => route('crop-planning.pdf', [
                    'delivery_date' => $this->data['delivery_date']
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getOrdersProperty(): Collection
    {
        return $this->orders ?? collect();
    }

    public function getPlantingPlanProperty(): ?array
    {
        return $this->plantingPlan;
    }
    
    public function getCalculationDetailsProperty(): ?array
    {
        return $this->calculationDetails;
    }
}