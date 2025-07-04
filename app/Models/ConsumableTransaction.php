<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConsumableTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'consumable_id',
        'type',
        'quantity',
        'balance_after',
        'user_id',
        'reference_type',
        'reference_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'metadata' => 'array',
    ];

    /**
     * Transaction types
     */
    const TYPE_CONSUMPTION = 'consumption';
    const TYPE_ADDITION = 'addition';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_WASTE = 'waste';
    const TYPE_EXPIRATION = 'expiration';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_INITIAL = 'initial';

    /**
     * Get the consumable this transaction belongs to.
     */
    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    /**
     * Get the user who performed this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (polymorphic).
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $modelClass = $this->getModelClassFromType($this->reference_type);
            if (class_exists($modelClass)) {
                return $this->belongsTo($modelClass, 'reference_id');
            }
        }
        return null;
    }

    /**
     * Convert reference type to model class.
     */
    protected function getModelClassFromType(string $type): string
    {
        $typeMap = [
            'order' => Order::class,
            'invoice' => Invoice::class,
            'crop' => Crop::class,
            'recipe' => Recipe::class,
            'product_mix' => ProductMix::class,
        ];

        return $typeMap[$type] ?? '';
    }

    /**
     * Check if this is an inbound transaction (adds stock).
     */
    public function isInbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_ADDITION,
            self::TYPE_TRANSFER_IN,
            self::TYPE_INITIAL,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity > 0);
    }

    /**
     * Check if this is an outbound transaction (removes stock).
     */
    public function isOutbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_CONSUMPTION,
            self::TYPE_WASTE,
            self::TYPE_EXPIRATION,
            self::TYPE_TRANSFER_OUT,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity < 0);
    }

    /**
     * Get human-readable type label.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_CONSUMPTION => 'Used in Production',
            self::TYPE_ADDITION => 'Stock Added',
            self::TYPE_ADJUSTMENT => 'Manual Adjustment',
            self::TYPE_WASTE => 'Waste/Damage',
            self::TYPE_EXPIRATION => 'Expired',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_INITIAL => 'Initial Stock',
        ];

        return $labels[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get the impact on inventory (positive or negative).
     */
    public function getImpact(): string
    {
        if ($this->quantity > 0) {
            return '+' . number_format(abs($this->quantity), 3);
        } else {
            return '-' . number_format(abs($this->quantity), 3);
        }
    }

    /**
     * Get all valid transaction types.
     */
    public static function getValidTypes(): array
    {
        return [
            self::TYPE_CONSUMPTION,
            self::TYPE_ADDITION,
            self::TYPE_ADJUSTMENT,
            self::TYPE_WASTE,
            self::TYPE_EXPIRATION,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_TRANSFER_IN,
            self::TYPE_INITIAL,
        ];
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'consumable_id',
                'type',
                'quantity',
                'balance_after',
                'user_id',
                'reference_type',
                'reference_id',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Create a new consumption transaction.
     */
    public static function createConsumption(
        Consumable $consumable,
        float $quantity,
        float $balanceAfter,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'consumable_id' => $consumable->id,
            'type' => self::TYPE_CONSUMPTION,
            'quantity' => -abs($quantity), // Always negative for consumption
            'balance_after' => $balanceAfter,
            'user_id' => $user?->id ?? auth()?->id(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a new addition transaction.
     */
    public static function createAddition(
        Consumable $consumable,
        float $quantity,
        float $balanceAfter,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'consumable_id' => $consumable->id,
            'type' => self::TYPE_ADDITION,
            'quantity' => abs($quantity), // Always positive for addition
            'balance_after' => $balanceAfter,
            'user_id' => $user?->id ?? auth()?->id(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }
}
