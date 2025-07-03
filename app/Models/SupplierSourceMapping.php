<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierSourceMapping extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'supplier_id',
        'source_name',
        'source_identifier',
        'source_url',
        'domain',
        'mapping_data',
        'is_active',
        'metadata'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'mapping_data' => 'array',
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
        $service = app(\App\Services\SupplierMatchingService::class);
        $domain = $service->extractDomain($sourceUrl);
        
        // Try exact URL match first
        $mapping = self::where('source_url', $sourceUrl)
            ->where('is_active', true)
            ->first();
            
        if ($mapping) {
            return $mapping;
        }
        
        // Try domain match
        return self::where('domain', $domain)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Create or update a mapping
     */
    public static function createMapping(string $sourceUrl, int $supplierId, array $metadata = []): self
    {
        $service = app(\App\Services\SupplierMatchingService::class);
        $domain = $service->extractDomain($sourceUrl);
        $domainName = $service->extractDomainName($domain);
        
        return self::updateOrCreate(
            [
                'supplier_id' => $supplierId,
                'domain' => $domain
            ],
            [
                'source_name' => $domainName,
                'source_identifier' => $domain,
                'source_url' => $sourceUrl,
                'domain' => $domain,
                'is_active' => true,
                'metadata' => $metadata
            ]
        );
    }
}
