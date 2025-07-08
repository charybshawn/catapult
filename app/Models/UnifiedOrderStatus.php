<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class UnifiedOrderStatus extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'badge_color',
        'stage',
        'requires_crops',
        'is_active',
        'is_final',
        'allows_modifications',
        'sort_order',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requires_crops' => 'boolean',
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'allows_modifications' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    /**
     * Stage constants for easy reference.
     */
    const STAGE_PRE_PRODUCTION = 'pre_production';
    const STAGE_PRODUCTION = 'production';
    const STAGE_FULFILLMENT = 'fulfillment';
    const STAGE_FINAL = 'final';
    
    /**
     * Status code constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_GROWING = 'growing';
    const STATUS_READY_TO_HARVEST = 'ready_to_harvest';
    const STATUS_HARVESTING = 'harvesting';
    const STATUS_PACKING = 'packing';
    const STATUS_READY_FOR_DELIVERY = 'ready_for_delivery';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_TEMPLATE = 'template';
    
    /**
     * Get the orders that have this status.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'unified_status_id');
    }
    
    /**
     * Check if this status is in the pre-production stage.
     */
    public function isPreProductionStage(): bool
    {
        return $this->stage === self::STAGE_PRE_PRODUCTION;
    }
    
    /**
     * Check if this status is in the production stage.
     */
    public function isProductionStage(): bool
    {
        return $this->stage === self::STAGE_PRODUCTION;
    }
    
    /**
     * Check if this status is in the fulfillment stage.
     */
    public function isFulfillmentStage(): bool
    {
        return $this->stage === self::STAGE_FULFILLMENT;
    }
    
    /**
     * Check if this status is in the final stage.
     */
    public function isFinalStage(): bool
    {
        return $this->stage === self::STAGE_FINAL;
    }
    
    /**
     * Check if orders with this status can be modified.
     */
    public function canBeModified(): bool
    {
        return $this->allows_modifications && !$this->is_final;
    }
    
    /**
     * Check if this status indicates the order is active.
     */
    public function isActiveOrder(): bool
    {
        return !$this->is_final && $this->code !== self::STATUS_TEMPLATE;
    }
    
    /**
     * Get the display color for badges or UI elements.
     */
    public function getDisplayColor(): string
    {
        return $this->badge_color ?: $this->color;
    }
    
    /**
     * Get the next logical status based on current stage.
     */
    public function getNextStatus(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('sort_order', '>', $this->sort_order)
            ->where('code', '!=', self::STATUS_TEMPLATE)
            ->orderBy('sort_order')
            ->first();
    }
    
    /**
     * Get the previous logical status based on current stage.
     */
    public function getPreviousStatus(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('sort_order', '<', $this->sort_order)
            ->where('code', '!=', self::STATUS_TEMPLATE)
            ->orderBy('sort_order', 'desc')
            ->first();
    }
    
    /**
     * Get all statuses for a specific stage.
     */
    public static function getByStage(string $stage): Collection
    {
        return self::query()
            ->where('stage', $stage)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Get all active statuses grouped by stage.
     */
    public static function getGroupedByStage(): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('stage');
    }
    
    /**
     * Get statuses that require crop production.
     */
    public static function getCropRequiredStatuses(): Collection
    {
        return self::query()
            ->where('requires_crops', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Get statuses that allow modifications.
     */
    public static function getModifiableStatuses(): Collection
    {
        return self::query()
            ->where('allows_modifications', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Get statuses for dropdown options.
     *
     * @param bool $includeInactive Whether to include inactive statuses
     * @param bool $groupByStage Whether to group options by stage
     * @return array
     */
    public static function getOptionsForDropdown(bool $includeInactive = false, bool $groupByStage = false): array
    {
        $query = self::query()->orderBy('sort_order');
        
        if (!$includeInactive) {
            $query->where('is_active', true);
        }
        
        $statuses = $query->get();
        
        if ($groupByStage) {
            $options = [];
            $stages = [
                self::STAGE_PRE_PRODUCTION => 'Pre-Production',
                self::STAGE_PRODUCTION => 'Production',
                self::STAGE_FULFILLMENT => 'Fulfillment',
                self::STAGE_FINAL => 'Final',
            ];
            
            foreach ($stages as $stageKey => $stageLabel) {
                $stageStatuses = $statuses->where('stage', $stageKey);
                if ($stageStatuses->isNotEmpty()) {
                    $options[$stageLabel] = $stageStatuses->pluck('name', 'id')->toArray();
                }
            }
            
            return $options;
        }
        
        return $statuses->pluck('name', 'id')->toArray();
    }
    
    /**
     * Find a status by its code.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
    
    /**
     * Get the default status for new orders.
     */
    public static function getDefaultStatus(): ?self
    {
        return self::findByCode(self::STATUS_PENDING);
    }
    
    /**
     * Get the template status for recurring orders.
     */
    public static function getTemplateStatus(): ?self
    {
        return self::findByCode(self::STATUS_TEMPLATE);
    }
    
    /**
     * Check if a transition from one status to another is valid.
     *
     * @param string $fromCode The current status code
     * @param string $toCode The target status code
     * @return bool
     */
    public static function isValidTransition(string $fromCode, string $toCode): bool
    {
        $fromStatus = self::findByCode($fromCode);
        $toStatus = self::findByCode($toCode);
        
        if (!$fromStatus || !$toStatus) {
            return false;
        }
        
        // Cannot transition from a final status
        if ($fromStatus->is_final) {
            return false;
        }
        
        // Cannot transition to template status (it's only set on creation)
        if ($toCode === self::STATUS_TEMPLATE) {
            return false;
        }
        
        // Allow cancellation from any non-final status
        if ($toCode === self::STATUS_CANCELLED) {
            return true;
        }
        
        // Generally allow forward progression
        if ($toStatus->sort_order > $fromStatus->sort_order) {
            return true;
        }
        
        // Allow backward transition only within the same stage for corrections
        if ($toStatus->sort_order < $fromStatus->sort_order && 
            $toStatus->stage === $fromStatus->stage && 
            $fromStatus->allows_modifications) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get valid next statuses for a given status code.
     *
     * @param string $currentCode The current status code
     * @return Collection
     */
    public static function getValidNextStatuses(string $currentCode): Collection
    {
        $currentStatus = self::findByCode($currentCode);
        
        if (!$currentStatus || $currentStatus->is_final) {
            return collect();
        }
        
        return self::query()
            ->where('is_active', true)
            ->where('code', '!=', self::STATUS_TEMPLATE)
            ->get()
            ->filter(function ($status) use ($currentCode) {
                return self::isValidTransition($currentCode, $status->code);
            })
            ->sortBy('sort_order');
    }
    
    /**
     * Get the stage display name.
     */
    public function getStageDisplayAttribute(): string
    {
        return match($this->stage) {
            self::STAGE_PRE_PRODUCTION => 'Pre-Production',
            self::STAGE_PRODUCTION => 'Production',
            self::STAGE_FULFILLMENT => 'Fulfillment',
            self::STAGE_FINAL => 'Final',
            default => 'Unknown',
        };
    }
    
    /**
     * Scope to get only active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope to get only non-final statuses.
     */
    public function scopeNotFinal($query)
    {
        return $query->where('is_final', false);
    }
    
    /**
     * Scope to get statuses by stage.
     */
    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }
    
    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}