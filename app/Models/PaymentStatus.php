<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Payment Status Management for Agricultural Business Financial Workflows
 *
 * Represents different payment processing states in the microgreens agricultural
 * business, controlling payment workflow progression and business rule enforcement.
 * Essential for financial operations, cash flow management, and agricultural
 * production timing based on payment confirmation.
 *
 * @property int $id Primary key identifier
 * @property string $code Unique system code for status identification
 * @property string $name Human-readable status name for display
 * @property string|null $description Detailed status explanation and workflow rules
 * @property string|null $color Display color for status visualization
 * @property bool $is_active Whether status is available for use
 * @property int|null $sort_order Status progression order
 * @property bool $is_final Whether status represents completed payment workflow
 * @property bool $allows_modifications Whether payments can be modified in this status
 *
 * @relationship payments HasMany Payments currently in this status
 *
 * @business_rule Final statuses prevent further payment modifications
 * @business_rule Status transitions validated against agricultural business rules
 * @business_rule Modification permissions control payment workflow flexibility
 * @business_rule Active status controls availability in payment processing
 *
 * @agricultural_context Payment statuses drive agricultural production workflows:
 * - pending: Payment processing in progress, agricultural production on hold
 * - completed: Payment confirmed, agricultural production authorized to proceed
 * - failed: Payment failed, agricultural production suspended pending resolution
 * - refunded: Payment reversed, agricultural production cancelled or completed
 *
 * Payment status directly impacts agricultural resource allocation, cultivation
 * scheduling, and customer delivery timing for optimal business operations.
 *
 * @usage_example
 * // Check if payment enables agricultural production
 * if ($payment->paymentStatus->isCompleted()) {
 *     // Proceed with agricultural production planning
 * }
 *
 * // Handle payment workflow progression
 * $nextStatus = $paymentStatus->getNextStatus();
 * if ($nextStatus && $payment->canBeModified()) {
 *     // Update payment status
 * }
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class PaymentStatus extends Model
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
        'is_active',
        'sort_order',
        'is_final',
        'allows_modifications',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'allows_modifications' => 'boolean',
    ];

    /**
     * Get payments currently in this status.
     *
     * Relationship to all payments using this status for agricultural
     * business financial tracking and workflow management. Essential
     * for payment status analysis and workflow progression monitoring.
     *
     * @return HasMany<Payment> Payments in this status
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'status_id');
    }

    /**
     * Find a payment status by its code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all active payment statuses for dropdowns
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get only active payment statuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Business Logic Methods

    /**
     * Check if this is a pending payment status
     */
    public function isPending(): bool
    {
        return $this->code === 'pending';
    }

    /**
     * Check if status represents successful payment completion.
     *
     * Determines if payment has been successfully processed and confirmed,
     * enabling agricultural production to proceed with resource allocation
     * and cultivation scheduling for microgreens orders.
     *
     * @return bool True if status is completed
     */
    public function isCompleted(): bool
    {
        return $this->code === 'completed';
    }

    /**
     * Check if this is a failed payment status
     */
    public function isFailed(): bool
    {
        return $this->code === 'failed';
    }

    /**
     * Check if this is a refunded payment status
     */
    public function isRefunded(): bool
    {
        return $this->code === 'refunded';
    }

    /**
     * Check if payment is successful (completed or refunded)
     */
    public function isSuccessful(): bool
    {
        return in_array($this->code, ['completed', 'refunded']);
    }

    /**
     * Check if payment status requires administrative attention.
     *
     * Determines if payment is in failed or pending state requiring
     * intervention to resolve issues and enable agricultural production
     * to proceed with customer orders.
     *
     * @return bool True if status needs attention
     */
    public function needsAttention(): bool
    {
        return in_array($this->code, ['failed', 'pending']);
    }

    /**
     * Check if payment can be modified
     */
    public function canBeModified(): bool
    {
        return $this->allows_modifications && !$this->is_final;
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->code === 'completed';
    }

    /**
     * Get the next logical status for agricultural payment workflow.
     *
     * Returns appropriate next status based on current payment state
     * and agricultural business workflow rules. Used for automated
     * payment processing and workflow progression.
     *
     * @return self|null Next appropriate payment status or null if final
     */
    public function getNextStatus(): ?self
    {
        return match($this->code) {
            'pending' => static::findByCode('completed'),
            'failed' => static::findByCode('pending'), // Allow retry
            default => null // completed/refunded are final
        };
    }

    /**
     * Get status color for UI display
     */
    public function getDisplayColor(): string
    {
        return $this->color ?? match($this->code) {
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'gray'
        };
    }
}