<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural seed supplier matching and identification service.
 * 
 * Intelligently matches scraped seed data with existing agricultural suppliers
 * using domain analysis, name similarity matching, and business context recognition.
 * Essential for automating seed catalog management and ensuring accurate
 * supplier attribution for agricultural product sourcing and pricing.
 *
 * @business_domain Agricultural seed supplier management and catalog automation
 * @related_models Supplier, SeedScrapeUpload, SeedEntry
 * @used_by Seed scraping system, supplier management, catalog import processes
 * @agricultural_context Automates supplier identification for seed catalog management
 */
class SupplierMatchingService
{
    /**
     * Find potential agricultural supplier matches for scraped seed data sources.
     * 
     * Analyzes source URLs from seed scraping operations to identify matching
     * agricultural suppliers in the system using domain analysis, name matching,
     * and business context recognition. Critical for automated seed catalog
     * management and supplier attribution.
     *
     * @param string $sourceUrl The source URL from scraped seed data
     * @return array Array of potential supplier matches including:
     *   - supplier: Matched Supplier model instance
     *   - confidence: Match confidence score (0.0 to 1.0)
     *   - match_reasons: Human-readable explanations for the match
     * @agricultural_context Automates supplier identification for seed catalog organization
     * @confidence_threshold Requires >30% confidence for inclusion in results
     * @logging Comprehensive logging for matching decision transparency
     */
    public function findPotentialMatches(string $sourceUrl): array
    {
        Log::info('SupplierMatchingService: Starting supplier matching process', [
            'source_url' => $sourceUrl,
            'timestamp' => now()->toISOString()
        ]);
        
        $domain = $this->extractDomain($sourceUrl);
        $domainName = $this->extractDomainName($domain);
        
        Log::info('SupplierMatchingService: Extracted domain information', [
            'source_url' => $sourceUrl,
            'extracted_domain' => $domain,
            'domain_name' => $domainName,
            'original_host' => parse_url($sourceUrl, PHP_URL_HOST)
        ]);
        
        $suppliers = Supplier::with('supplierType')->where('is_active', true)->get();
        $matches = [];
        
        Log::debug('SupplierMatchingService: Loaded active suppliers for matching', [
            'supplier_count' => $suppliers->count(),
            'source_url' => $sourceUrl
        ]);
        
        foreach ($suppliers as $supplier) {
            Log::debug('SupplierMatchingService: Evaluating supplier', [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'supplier_type' => $supplier->supplierType?->code
            ]);
            $confidence = $this->calculateMatchConfidence($supplier, $domain, $domainName, $sourceUrl);
            
            Log::debug('SupplierMatchingService: Calculated confidence for supplier', [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'confidence' => $confidence,
                'meets_threshold' => $confidence > 0.3
            ]);
            
            if ($confidence > 0.3) { // Only include matches with >30% confidence
                $matchReasons = $this->getMatchReasons($supplier, $domain, $domainName, $sourceUrl);
                
                $matches[] = [
                    'supplier' => $supplier,
                    'confidence' => $confidence,
                    'match_reasons' => $matchReasons
                ];
                
                Log::info('SupplierMatchingService: Added supplier to matches', [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'confidence' => $confidence,
                    'match_reasons' => $matchReasons
                ]);
            }
        }
        
        // Sort by confidence (highest first)
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        
        Log::info('SupplierMatchingService: Completed supplier matching', [
            'source_url' => $sourceUrl,
            'matches_count' => count($matches),
            'top_match_confidence' => $matches[0]['confidence'] ?? 0,
            'top_match_supplier' => isset($matches[0]) ? $matches[0]['supplier']->name : null,
            'all_matches' => collect($matches)->map(function($m) {
                return [
                    'supplier_name' => $m['supplier']->name,
                    'confidence' => round($m['confidence'] * 100) . '%'
                ];
            })->toArray()
        ]);
        
        return $matches;
    }
    
    /**
     * Extract clean domain name from agricultural supplier URLs.
     * 
     * Parses URLs from seed supplier websites to extract standardized
     * domain names for matching purposes. Handles common URL formats
     * from agricultural seed supplier websites.
     *
     * @param string $url Full URL from agricultural supplier website
     * @return string Clean domain name (e.g., "damseeds.com")
     * @agricultural_context Standardizes domain extraction from seed supplier websites
     */
    public function extractDomain(string $url): string
    {
        $parsed = parse_url(strtolower($url));
        $host = $parsed['host'] ?? $url;
        
        Log::debug('SupplierMatchingService: Extracting domain', [
            'original_url' => $url,
            'parsed_host' => $host,
            'has_www' => strpos($host, 'www.') === 0
        ]);
        
        // Remove www. prefix
        $domain = preg_replace('/^www\./', '', $host);
        
        Log::debug('SupplierMatchingService: Domain extracted', [
            'final_domain' => $domain
        ]);
        
        return $domain;
    }
    
    /**
     * Extract base supplier name from domain for matching analysis.
     * 
     * Removes top-level domain to get the core business name for
     * agricultural supplier matching. Essential for fuzzy matching
     * with supplier names that may not include domain extensions.
     *
     * @param string $domain Full domain name (e.g., "damseeds.com")
     * @return string Base domain name (e.g., "damseeds")
     * @agricultural_context Facilitates matching with agricultural supplier business names
     */
    public function extractDomainName(string $domain): string
    {
        $parts = explode('.', $domain);
        return $parts[0] ?? $domain;
    }
    
    /**
     * Calculate confidence score for agricultural supplier match accuracy.
     * 
     * Uses multiple matching algorithms to determine how likely a supplier
     * is to match scraped seed data sources. Considers exact domain matches,
     * name similarity, business context (seed suppliers), and common
     * agricultural business terminology for comprehensive matching.
     *
     * @param Supplier $supplier The agricultural supplier to evaluate
     * @param string $domain Clean domain from source URL
     * @param string $domainName Base domain name without TLD
     * @param string $sourceUrl Original source URL for additional matching
     * @return float Confidence score from 0.0 (no match) to 1.0 (perfect match)
     * @agricultural_context Prioritizes seed suppliers and agricultural business patterns
     * @confidence_factors Exact domain (0.9), domain name (0.8), fuzzy similarity, business terms
     */
    protected function calculateMatchConfidence(Supplier $supplier, string $domain, string $domainName, string $sourceUrl): float
    {
        $confidence = 0.0;
        $supplierName = strtolower($supplier->name);
        
        Log::debug('SupplierMatchingService: Starting confidence calculation', [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'supplier_name_lower' => $supplierName,
            'domain' => $domain,
            'domain_name' => $domainName
        ]);
        
        // Exact domain match in supplier name (very high confidence)
        if (strpos($supplierName, $domain) !== false) {
            $confidence += 0.9;
            Log::debug('SupplierMatchingService: Exact domain match found', [
                'supplier_name' => $supplierName,
                'domain' => $domain,
                'confidence_added' => 0.9
            ]);
        }
        
        // Domain name match (high confidence)
        elseif (strpos($supplierName, $domainName) !== false) {
            $confidence += 0.8;
            Log::debug('SupplierMatchingService: Domain name match found', [
                'supplier_name' => $supplierName,
                'domain_name' => $domainName,
                'confidence_added' => 0.8
            ]);
        }
        
        // Check if supplier name contains URL
        elseif (strpos($supplierName, $sourceUrl) !== false) {
            $confidence += 0.95;
        }
        
        // Fuzzy match on domain name (medium confidence)
        else {
            $similarity = 0;
            similar_text($domainName, $supplierName, $similarity);
            if ($similarity > 70) {
                $confidenceAdded = 0.6 * ($similarity / 100);
                $confidence += $confidenceAdded;
                Log::debug('SupplierMatchingService: Fuzzy match found', [
                    'supplier_name' => $supplierName,
                    'domain_name' => $domainName,
                    'similarity_percent' => $similarity,
                    'confidence_added' => $confidenceAdded
                ]);
            }
        }
        
        // Check for common business name patterns
        $businessWords = ['seeds', 'organic', 'garden', 'farm', 'nursery', 'greenhouse', 'supply'];
        $matchedWords = [];
        foreach ($businessWords as $word) {
            if (strpos($supplierName, $word) !== false && strpos($domainName, $word) !== false) {
                $confidence += 0.1;
                $matchedWords[] = $word;
            }
        }
        
        if (!empty($matchedWords)) {
            Log::debug('SupplierMatchingService: Business word matches found', [
                'matched_words' => $matchedWords,
                'confidence_added' => count($matchedWords) * 0.1
            ]);
        }
        
        // Penalize if supplier has different type
        $supplierTypeCode = $supplier->supplierType?->code;
        if ($supplierTypeCode && $supplierTypeCode !== 'seed') {
            $originalConfidence = $confidence;
            $confidence *= 0.7;
            Log::debug('SupplierMatchingService: Penalized for non-seed type', [
                'supplier_type' => $supplierTypeCode,
                'original_confidence' => $originalConfidence,
                'penalized_confidence' => $confidence
            ]);
        }
        
        $finalConfidence = min(1.0, $confidence); // Cap at 100%
        
        Log::debug('SupplierMatchingService: Final confidence calculated', [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'raw_confidence' => $confidence,
            'final_confidence' => $finalConfidence
        ]);
        
        return $finalConfidence;
    }
    
    /**
     * Generate human-readable explanations for agricultural supplier matches.
     * 
     * Provides transparent explanations of why a supplier was matched
     * to scraped seed data, enabling users to understand and validate
     * automated supplier attribution decisions.
     *
     * @param Supplier $supplier The matched agricultural supplier
     * @param string $domain Clean domain from source URL
     * @param string $domainName Base domain name without TLD
     * @param string $sourceUrl Original source URL for matching
     * @return array Human-readable match explanations
     * @agricultural_context Explains supplier matching logic for seed catalog management
     */
    protected function getMatchReasons(Supplier $supplier, string $domain, string $domainName, string $sourceUrl): array
    {
        $reasons = [];
        $supplierName = strtolower($supplier->name);
        
        if (strpos($supplierName, $domain) !== false) {
            $reasons[] = "Exact domain match: '{$domain}' found in supplier name";
        }
        
        if (strpos($supplierName, $domainName) !== false) {
            $reasons[] = "Domain name match: '{$domainName}' found in supplier name";
        }
        
        if (strpos($supplierName, $sourceUrl) !== false) {
            $reasons[] = "Full URL match found in supplier name";
        }
        
        $similarity = 0;
        similar_text($domainName, $supplierName, $similarity);
        if ($similarity > 70) {
            $reasons[] = sprintf("High name similarity: %.1f%% match with domain name", $similarity);
        }
        
        if ($supplier->supplierType?->code === 'seed') {
            $reasons[] = "Supplier type matches (seed supplier)";
        }
        
        return $reasons;
    }
    
    /**
     * Generate suggested agricultural supplier name from website URL.
     * 
     * Creates intelligent supplier name suggestions based on domain analysis
     * and agricultural business naming conventions. Useful for creating
     * new supplier records when no existing match is found.
     *
     * @param string $sourceUrl URL from agricultural seed supplier website
     * @return string Suggested supplier name in proper business format
     * @agricultural_context Applies agricultural business naming conventions
     * @naming_rules Converts domain to proper case, adds "Seeds" suffix if needed
     */
    public function suggestSupplierName(string $sourceUrl): string
    {
        Log::info('SupplierMatchingService: Generating supplier name suggestion', [
            'source_url' => $sourceUrl
        ]);
        
        $domain = $this->extractDomain($sourceUrl);
        $domainName = $this->extractDomainName($domain);
        
        // Convert domain name to proper case
        $suggested = ucwords(str_replace(['-', '_'], ' ', $domainName));
        
        // Add common business suffix if not present
        if (!preg_match('/\b(seeds?|organic|garden|farm|nursery|greenhouse|supply)\b/i', $suggested)) {
            $suggested .= ' Seeds';
            Log::debug('SupplierMatchingService: Added default suffix to suggestion', [
                'suffix_added' => 'Seeds'
            ]);
        }
        
        Log::info('SupplierMatchingService: Generated supplier name suggestion', [
            'source_url' => $sourceUrl,
            'domain' => $domain,
            'domain_name' => $domainName,
            'suggested_name' => $suggested
        ]);
        
        return $suggested;
    }
    
    /**
     * Extract website URL from agricultural supplier name when embedded.
     * 
     * Searches supplier names for embedded URLs or domain names that
     * can be converted to website URLs. Useful for suppliers that
     * include their website in their business name.
     *
     * @param string $supplierName Agricultural supplier business name
     * @return string|null Extracted or constructed website URL
     * @agricultural_context Handles agricultural supplier naming patterns with embedded URLs
     * @url_formats Detects full URLs, domain names, and constructs HTTPS URLs
     */
    public function extractWebsiteFromSupplierName(string $supplierName): ?string
    {
        Log::debug('SupplierMatchingService: Attempting to extract website from supplier name', [
            'supplier_name' => $supplierName
        ]);
        
        // Check if name contains a URL
        if (preg_match('/https?:\/\/[^\s]+/', $supplierName, $matches)) {
            Log::info('SupplierMatchingService: Found full URL in supplier name', [
                'supplier_name' => $supplierName,
                'extracted_url' => $matches[0]
            ]);
            return $matches[0];
        }
        
        // Check if name contains a domain (e.g., "damseeds.com")
        if (preg_match('/([a-zA-Z0-9-]+\.[a-zA-Z]{2,})/', $supplierName, $matches)) {
            $url = 'https://' . $matches[1];
            Log::info('SupplierMatchingService: Found domain in supplier name', [
                'supplier_name' => $supplierName,
                'extracted_domain' => $matches[1],
                'constructed_url' => $url
            ]);
            return $url;
        }
        
        Log::debug('SupplierMatchingService: No website found in supplier name', [
            'supplier_name' => $supplierName
        ]);
        
        return null;
    }
}