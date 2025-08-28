<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Payment Method Management for Agricultural Business Financial Processing
 *
 * Represents different payment methods available for agricultural microgreens
 * orders, defining processing requirements, workflows, and customer payment
 * options. Essential for agricultural business financial operations and
 * customer experience optimization.
 *
 * @property int $id Primary key identifier
 * @property string $code Unique system code for method identification
 * @property string $name Human-readable method name for display
 * @property string|null $description Detailed method explanation and usage
 * @property string|null $color Display color for method visualization
 * @property bool $is_active Whether method is available for customer use
 * @property bool $requires_processing Whether method needs online processing
 * @property int|null $sort_order Display order for method prioritization
 *
 * @relationship payments HasMany Payments processed using this method
 *
 * @business_rule Active methods control customer payment options availability
 * @business_rule Processing requirements determine payment workflow automation
 * @business_rule Sort order influences customer payment method preferences
 *
 * @agricultural_context Payment methods support different agricultural business models:
 * - stripe: Online credit card processing for retail agricultural customers
 * - e-transfer: Electronic bank transfers for business agricultural customers
 * - cash: Direct cash payments for farmers market agricultural sales
 * - invoice: Net terms billing for established wholesale agricultural relationships
 *
 * Each method impacts agricultural business cash flow timing, processing costs,
 * and customer relationship management strategies.
 *
 * @usage_example
 * // Check payment processing requirements
 * if ($paymentMethod->requiresOnlineProcessing()) {
 *     // Process through payment gateway
 * } else {
 *     // Handle offline payment workflow
 * }
 *
 * // Get appropriate methods for customer type
 * $retailMethods = PaymentMethod::active()
 *     ->where('requires_processing', true)->get();
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class PaymentMethod extends Model
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
        'requires_processing',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'requires_processing' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get payments processed using this method.
     *
     * Relationship to all payment transactions using this payment method.
     * Essential for analyzing payment method usage patterns, processing
     * volumes, and agricultural business financial performance by method.
     *
     * @return HasMany<Payment> Payments using this method
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get active payment methods formatted for dropdown options.
     *
     * Returns array of active payment methods for customer selection
     * in agricultural order processing. Ordered by priority and name
     * to optimize customer payment experience.
     *
     * @return array<int, string> Payment method options keyed by ID
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all active payment methods.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find payment method by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if method uses Stripe online credit card processing.
     *
     * Determines if payment method processes credit cards through Stripe
     * gateway. Used for agricultural retail customer payments requiring
     * immediate online processing and payment confirmation.
     *
     * @return bool True if method is Stripe credit card processing
     */
    public function isStripe(): bool
    {
        return $this->code === 'stripe';
    }

    /**
     * Check if this is e-transfer payment method.
     */
    public function isETransfer(): bool
    {
        return $this->code === 'e-transfer';
    }

    /**
     * Check if this is cash payment method.
     */
    public function isCash(): bool
    {
        return $this->code === 'cash';
    }

    /**
     * Check if this is invoice payment method.
     */
    public function isInvoice(): bool
    {
        return $this->code === 'invoice';
    }

    /**
     * Check if method requires online payment gateway processing.
     *
     * Determines if payment method needs real-time online processing
     * through payment gateways. Affects agricultural workflow timing
     * and customer payment confirmation requirements.
     *
     * @return bool True if method requires online processing
     */
    public function requiresOnlineProcessing(): bool
    {
        return $this->requires_processing;
    }

    /**
     * Check if this is an offline payment method.
     */
    public function isOfflineMethod(): bool
    {
        return !$this->requires_processing;
    }
}