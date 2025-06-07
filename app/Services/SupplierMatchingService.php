<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SupplierMatchingService
{
    /**
     * Find potential supplier matches for a given source URL
     * 
     * @param string $sourceUrl The source URL from scraped data
     * @return array Array of potential matches with confidence scores
     */
    public function findPotentialMatches(string $sourceUrl): array
    {
        $domain = $this->extractDomain($sourceUrl);
        $domainName = $this->extractDomainName($domain);
        
        Log::info('Finding supplier matches', [
            'source_url' => $sourceUrl,
            'extracted_domain' => $domain,
            'domain_name' => $domainName
        ]);
        
        $suppliers = Supplier::where('is_active', true)->get();
        $matches = [];
        
        foreach ($suppliers as $supplier) {
            $confidence = $this->calculateMatchConfidence($supplier, $domain, $domainName, $sourceUrl);
            
            if ($confidence > 0.3) { // Only include matches with >30% confidence
                $matches[] = [
                    'supplier' => $supplier,
                    'confidence' => $confidence,
                    'match_reasons' => $this->getMatchReasons($supplier, $domain, $domainName, $sourceUrl)
                ];
            }
        }
        
        // Sort by confidence (highest first)
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        
        Log::info('Found supplier matches', [
            'source_url' => $sourceUrl,
            'matches_count' => count($matches),
            'top_match_confidence' => $matches[0]['confidence'] ?? 0
        ]);
        
        return $matches;
    }
    
    /**
     * Extract domain from URL (e.g., "damseeds.com" from "https://www.damseeds.com/products/...")
     */
    public function extractDomain(string $url): string
    {
        $parsed = parse_url(strtolower($url));
        $host = $parsed['host'] ?? $url;
        
        // Remove www. prefix
        return preg_replace('/^www\./', '', $host);
    }
    
    /**
     * Extract domain name without TLD (e.g., "damseeds" from "damseeds.com")
     */
    public function extractDomainName(string $domain): string
    {
        $parts = explode('.', $domain);
        return $parts[0] ?? $domain;
    }
    
    /**
     * Calculate confidence score for a supplier match
     */
    protected function calculateMatchConfidence(Supplier $supplier, string $domain, string $domainName, string $sourceUrl): float
    {
        $confidence = 0.0;
        $supplierName = strtolower($supplier->name);
        
        // Exact domain match in supplier name (very high confidence)
        if (strpos($supplierName, $domain) !== false) {
            $confidence += 0.9;
        }
        
        // Domain name match (high confidence)
        elseif (strpos($supplierName, $domainName) !== false) {
            $confidence += 0.8;
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
                $confidence += 0.6 * ($similarity / 100);
            }
        }
        
        // Check for common business name patterns
        $businessWords = ['seeds', 'organic', 'garden', 'farm', 'nursery', 'greenhouse', 'supply'];
        foreach ($businessWords as $word) {
            if (strpos($supplierName, $word) !== false && strpos($domainName, $word) !== false) {
                $confidence += 0.1;
            }
        }
        
        // Penalize if supplier has different type
        if ($supplier->type && $supplier->type !== 'seed') {
            $confidence *= 0.7;
        }
        
        return min(1.0, $confidence); // Cap at 100%
    }
    
    /**
     * Get human-readable reasons why this supplier matches
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
        
        if ($supplier->type === 'seed') {
            $reasons[] = "Supplier type matches (seed supplier)";
        }
        
        return $reasons;
    }
    
    /**
     * Create a supplier name suggestion from URL
     */
    public function suggestSupplierName(string $sourceUrl): string
    {
        $domain = $this->extractDomain($sourceUrl);
        $domainName = $this->extractDomainName($domain);
        
        // Convert domain name to proper case
        $suggested = ucwords(str_replace(['-', '_'], ' ', $domainName));
        
        // Add common business suffix if not present
        if (!preg_match('/\b(seeds?|organic|garden|farm|nursery|greenhouse|supply)\b/i', $suggested)) {
            $suggested .= ' Seeds';
        }
        
        return $suggested;
    }
    
    /**
     * Extract website URL for a supplier from various name formats
     */
    public function extractWebsiteFromSupplierName(string $supplierName): ?string
    {
        // Check if name contains a URL
        if (preg_match('/https?:\/\/[^\s]+/', $supplierName, $matches)) {
            return $matches[0];
        }
        
        // Check if name contains a domain (e.g., "damseeds.com")
        if (preg_match('/([a-zA-Z0-9-]+\.[a-zA-Z]{2,})/', $supplierName, $matches)) {
            return 'https://' . $matches[1];
        }
        
        return null;
    }
}