<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierSourceMapping extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'source_url',
        'domain', 
        'supplier_id',
        'is_active',
        'metadata'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];
    
    /**
     * Get the supplier this mapping belongs to
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Find a mapping for a given source URL or domain
     */
    public static function findMappingForSource(string $sourceUrl): ?self
    {
        // Try exact URL first
        $mapping = self::where('source_url', $sourceUrl)
            ->where('is_active', true)
            ->first();
            
        if ($mapping) {
            return $mapping;
        }
        
        // Try domain match
        $domain = app(\App\Services\SupplierMatchingService::class)->extractDomain($sourceUrl);
        
        return self::where('domain', $domain)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Create or update a mapping
     */
    public static function createMapping(string $sourceUrl, int $supplierId, array $metadata = []): self
    {
        $domain = app(\App\Services\SupplierMatchingService::class)->extractDomain($sourceUrl);
        
        return self::updateOrCreate(
            [
                'domain' => $domain,
                'supplier_id' => $supplierId
            ],
            [
                'source_url' => $sourceUrl,
                'is_active' => true,
                'metadata' => $metadata
            ]
        );
    }
}
