<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Exception;
use Filament\Actions\Action;
use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Agricultural consumable stock adjustment page for inventory management.
 *
 * Provides specialized interface for adding or consuming agricultural supplies
 * with support for different consumable types including seeds, soil, packaging,
 * and production materials. Features lot number tracking for seeds, unit
 * conversions, and transactional stock operations for accurate inventory control.
 *
 * @filament_page Custom page for consumable stock adjustments
 * @business_domain Agricultural inventory management and stock control
 * @inventory_operations Add stock, consume stock with unit conversion support
 * @seed_specialization Lot number tracking and separate inventory records
 * @transaction_safety Database transactions with rollback on errors
 */
class AdjustStock extends Page
{
    /** @var string Associated Filament resource class */
    protected static string $resource = ConsumableResource::class;

    /** @var string Blade view template for stock adjustment */
    protected string $view = 'filament.resources.consumable-resource.pages.adjust-stock';

    /** @var Consumable|null Current consumable record for adjustment */
    public ?Consumable $record = null;

    /**
     * Initialize page with consumable record and default form values.
     *
     * Sets up the stock adjustment page with current stock information
     * and default adjustment type for agricultural inventory management.
     *
     * @param Consumable $record Consumable to adjust stock for
     */
    public function mount(Consumable $record): void
    {
        $this->record = $record;
        $this->form->fill([
            'current_stock' => $record->current_stock,
            'record_id' => $record->id,
            'adjustment_type' => 'add',
        ]);
    }

    /**
     * Configure comprehensive stock adjustment form for agricultural supplies.
     *
     * Creates form with adjustment type selection, current stock display,
     * seed information, and conditional fields for adding or consuming stock.
     * Features lot number tracking for seeds and unit conversion support
     * for accurate agricultural inventory management.
     *
     * @param Schema $schema Filament form schema for configuration
     * @return Schema Configured stock adjustment form
     * @form_features Add/consume selection, seed info, lot tracking, unit conversion
     */
    public function form(Schema $schema): Schema
    {
        $record = $this->record;

        return $schema
            ->components([
                Section::make('Stock Adjustment')
                    ->description('Adjust the stock level for this consumable')
                    ->schema([
                        Select::make('adjustment_type')
                            ->label('Action')
                            ->options([
                                'add' => 'Add Stock',
                                'consume' => 'Consume Stock',
                            ])
                            ->default('add')
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live(onBlur: true)
                            ->dehydrated(true),

                        TextInput::make('current_stock')
                            ->label('Current Stock')
                            ->suffix(function () use ($record) {
                                return $record->unit;
                            })
                            ->required()
                            ->disabled(),

                        Hidden::make('record_id')
                            ->default($record->id),

                        Placeholder::make('seed_info')
                            ->label('Seed Information')
                            ->content(function () use ($record) {
                                if ($record->type === 'seed') {
                                    $info = [];

                                    // Add master cultivar info if available
                                    if ($record->masterCultivar) {
                                        $info[] = "Cultivar: {$record->masterCultivar->name}";
                                        if ($record->masterCultivar->crop_type) {
                                            $info[] = "Crop Type: {$record->masterCultivar->crop_type}";
                                        }
                                    }

                                    // Add seed entry info if available
                                    if ($record->seedEntry) {
                                        if ($record->seedEntry->germination_rate) {
                                            $info[] = "Germination Rate: {$record->seedEntry->germination_rate}%";
                                        }
                                        if ($record->seedEntry->days_to_maturity) {
                                            $info[] = "Days to Maturity: {$record->seedEntry->days_to_maturity}";
                                        }
                                    }

                                    // Add cultivar name if set
                                    if ($record->cultivar) {
                                        $info[] = "Variety: {$record->cultivar}";
                                    }

                                    if (empty($info)) {
                                        return 'No seed details available';
                                    }

                                    return implode(' | ', $info);
                                }

                                return null;
                            })
                            ->visible(fn () => $record->type === 'seed'),
                    ])
                    ->columns(2),

                Section::make()
                    ->schema([
                        // Add stock fields - visible when adjustment_type is 'add'
                        Grid::make()
                            ->schema([
                                TextInput::make('add_amount')
                                    ->label('Amount to Add')
                                    ->numeric()
                                    ->step(0.001)
                                    ->minValue(0.001)
                                    ->required()
                                    ->default(fn () => $record->restock_quantity),
                                Select::make('add_unit')
                                    ->label('Unit')
                                    ->options(fn () => ConsumableResource::getCompatibleUnits($record))
                                    ->default(fn () => $record->unit)
                                    ->required(),
                                TextInput::make('lot_number')
                                    ->label('Lot/Batch Number')
                                    ->helperText('Required for seed inventory. Different lot numbers create separate inventory records.')
                                    ->maxLength(100)
                                    ->required(fn () => $record->type === 'seed')
                                    ->visible(fn () => $record->type === 'seed'),
                            ])
                            ->visible(fn (Get $get): bool => $get('adjustment_type') === 'add')
                            ->columns(2),

                        // Consume stock fields - visible when adjustment_type is 'consume'
                        Grid::make()
                            ->schema([
                                TextInput::make('consume_amount')
                                    ->label('Amount to Consume')
                                    ->numeric()
                                    ->step(0.001)
                                    ->minValue(0.001)
                                    ->required()
                                    ->default(1),
                                Select::make('consume_unit')
                                    ->label('Unit')
                                    ->options(fn () => ConsumableResource::getCompatibleUnits($record))
                                    ->default(fn () => $record->unit)
                                    ->required(),
                            ])
                            ->visible(fn (Get $get): bool => $get('adjustment_type') === 'consume')
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Execute stock adjustment operation with transaction safety.
     *
     * Processes add or consume operations for agricultural consumables with
     * specialized handling for seeds (lot number tracking), unit conversions,
     * and automatic creation of separate inventory records when needed.
     * Uses database transactions for data integrity.
     *
     * @throws Exception When stock adjustment operations fail
     * @business_logic Seeds with different lot numbers create separate records
     * @transaction_safety Database rollback on errors for inventory accuracy
     */
    public function adjustStock(): void
    {
        $data = $this->form->getState();

        $record = Consumable::findOrFail($data['record_id']);

        // Determine action based on selected type
        $adjustmentType = $data['adjustment_type'] ?? 'add';

        try {
            DB::beginTransaction();

            if ($adjustmentType === 'add' && isset($data['add_amount'])) {
                // For seed consumables, check if we need to create a new inventory record based on lot number
                if ($record->type === 'seed' && ! empty($data['lot_number'])) {
                    $result = $record->add((float) $data['add_amount'], $data['add_unit'] ?? null, $data['lot_number'] ?? null);

                    // If add() returns false, it means the lot numbers don't match and we need to create a new record
                    if ($result === false) {
                        // Create a new consumable record with the same properties but different lot number
                        $newConsumable = $record->replicate(['id', 'consumed_quantity', 'initial_stock', 'total_quantity', 'lot_no']);
                        $newConsumable->lot_no = $data['lot_number'];
                        $newConsumable->initial_stock = (float) $data['add_amount'];
                        $newConsumable->consumed_quantity = 0;

                        if ($record->quantity_per_unit) {
                            $newConsumable->total_quantity = (float) $data['add_amount'] * $record->quantity_per_unit;
                        } else {
                            $newConsumable->total_quantity = (float) $data['add_amount'];
                        }

                        $newConsumable->last_ordered_at = now();
                        $newConsumable->save();

                        $this->notify('success', 'New seed inventory record created with different lot number');
                    } else {
                        $this->notify('success', 'Stock added successfully');
                    }
                } else {
                    // For non-seed consumables or seeds without lot number, just add to existing stock
                    $record->add((float) $data['add_amount'], $data['add_unit'] ?? null);
                    $this->notify('success', 'Stock added successfully');
                }
            } elseif ($adjustmentType === 'consume' && isset($data['consume_amount'])) {
                $record->deduct((float) $data['consume_amount'], $data['consume_unit'] ?? null);
                $this->notify('success', 'Stock consumed successfully');
            } else {
                $this->notify('danger', 'Invalid stock adjustment data');
                DB::rollBack();

                return;
            }

            DB::commit();

            // Redirect back to consumable list
            $this->redirect(ConsumableResource::getUrl());

        } catch (Exception $e) {
            DB::rollBack();
            $this->notify('danger', 'Error adjusting stock: '.$e->getMessage());
        }
    }

    /**
     * Configure header actions for stock adjustment workflow.
     *
     * Provides save and cancel actions for stock adjustment operations
     * with appropriate routing back to consumable resource listing.
     *
     * @return array Filament actions for stock adjustment workflow
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('adjustStock'),

            Action::make('cancel')
                ->label('Cancel')
                ->url(ConsumableResource::getUrl()),
        ];
    }
}
