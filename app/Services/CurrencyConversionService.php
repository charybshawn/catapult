<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConversionService
{
    // Static exchange rates as fallback (update these periodically)
    const FALLBACK_RATES = [
        'USD_TO_CAD' => 1.35,
        'CAD_TO_USD' => 0.74,
    ];
    
    /**
     * Convert USD to CAD
     */
    public function usdToCad(float $usdAmount): float
    {
        $rate = $this->getExchangeRate('USD', 'CAD');
        return round($usdAmount * $rate, 2);
    }
    
    /**
     * Convert CAD to USD
     */
    public function cadToUsd(float $cadAmount): float
    {
        $rate = $this->getExchangeRate('CAD', 'USD');
        return round($cadAmount * $rate, 2);
    }
    
    /**
     * Convert any currency to CAD
     */
    public function convertToCad(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === 'CAD') {
            return $amount;
        }
        
        if ($fromCurrency === 'USD') {
            return $this->usdToCad($amount);
        }
        
        // For other currencies, use fallback rate or return original
        Log::warning("Currency conversion not supported for {$fromCurrency}, returning original amount");
        return $amount;
    }
    
    /**
     * Convert any currency to USD
     */
    public function convertToUsd(float $amount, string $fromCurrency): float
    {
        if ($fromCurrency === 'USD') {
            return $amount;
        }
        
        if ($fromCurrency === 'CAD') {
            return $this->cadToUsd($amount);
        }
        
        // For other currencies, use fallback rate or return original
        Log::warning("Currency conversion not supported for {$fromCurrency}, returning original amount");
        return $amount;
    }
    
    /**
     * Get the current exchange rate between two currencies
     */
    protected function getExchangeRate(string $from, string $to): float
    {
        $cacheKey = "exchange_rate_{$from}_to_{$to}";
        
        // Try to get from cache first with extended fallback caching
        $cachedRate = Cache::get($cacheKey);
        if ($cachedRate !== null) {
            return $cachedRate;
        }
        
        // If not in cache, try to fetch live rate
        try {
            $liveRate = $this->fetchLiveExchangeRate($from, $to);
            if ($liveRate !== null) {
                // Cache live rate for 6 hours
                Cache::put($cacheKey, $liveRate, now()->addHours(6));
                // Also cache in a fallback key for 7 days in case API fails later
                Cache::put("{$cacheKey}_fallback", $liveRate, now()->addDays(7));
                Log::info("Retrieved and cached live exchange rate: {$from} to {$to} = {$liveRate}");
                return $liveRate;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch live exchange rate: " . $e->getMessage());
        }
        
        // Try extended cache fallback (up to 7 days old)
        $fallbackCachedRate = Cache::get("{$cacheKey}_fallback");
        if ($fallbackCachedRate !== null) {
            // Cache for shorter time since it's stale
            Cache::put($cacheKey, $fallbackCachedRate, now()->addHours(1));
            Log::info("Using cached fallback exchange rate: {$from} to {$to} = {$fallbackCachedRate}");
            return $fallbackCachedRate;
        }
        
        // Fallback to static rates
        $fallbackKey = "{$from}_TO_{$to}";
        if (isset(self::FALLBACK_RATES[$fallbackKey])) {
            $staticRate = self::FALLBACK_RATES[$fallbackKey];
            // Cache static rate for 1 hour
            Cache::put($cacheKey, $staticRate, now()->addHour());
            Log::info("Using static fallback exchange rate: {$from} to {$to} = {$staticRate}");
            return $staticRate;
        }
        
        // Ultimate fallback
        Log::warning("No exchange rate available for {$from} to {$to}, using 1.0");
        Cache::put($cacheKey, 1.0, now()->addMinutes(30)); // Cache for short time to avoid repeated API calls
        return 1.0;
    }
    
    /**
     * Fetch live exchange rate from API
     */
    protected function fetchLiveExchangeRate(string $from, string $to): ?float
    {
        $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/' . $from);
        
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['rates'][$to])) {
                return (float) $data['rates'][$to];
            }
        }
        
        return null;
    }
    
    /**
     * Get formatted currency string with conversion info
     */
    public function getFormattedConversion(float $amount, string $fromCurrency, string $toCurrency): string
    {
        if ($fromCurrency === $toCurrency) {
            return $this->formatCurrency($amount, $fromCurrency);
        }
        
        $convertedAmount = $toCurrency === 'CAD' 
            ? $this->convertToCad($amount, $fromCurrency)
            : $this->convertToUsd($amount, $fromCurrency);
            
        return $this->formatCurrency($convertedAmount, $toCurrency);
    }
    
    /**
     * Get formatted currency string with detailed conversion info
     */
    public function getFormattedConversionWithDetails(float $amount, string $fromCurrency, string $toCurrency): string
    {
        if ($fromCurrency === $toCurrency) {
            return $this->formatCurrency($amount, $fromCurrency);
        }
        
        $convertedAmount = $toCurrency === 'CAD' 
            ? $this->convertToCad($amount, $fromCurrency)
            : $this->convertToUsd($amount, $fromCurrency);
            
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        return $this->formatCurrency($convertedAmount, $toCurrency) . 
               " (from " . $this->formatCurrency($amount, $fromCurrency) . 
               " @ {$rate})";
    }
    
    /**
     * Format currency amount with proper symbol
     */
    protected function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => 'USD$',
            'CAD' => 'CDN$',
            'EUR' => '€',
            'GBP' => '£',
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }
    
    /**
     * Clear all cached exchange rates
     */
    public function clearCache(): void
    {
        $currencies = ['USD', 'CAD', 'EUR', 'GBP'];
        
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from !== $to) {
                    Cache::forget("exchange_rate_{$from}_to_{$to}");
                    Cache::forget("exchange_rate_{$from}_to_{$to}_fallback");
                }
            }
        }
        
        Log::info('Currency conversion cache cleared');
    }
    
    /**
     * Warm up cache with current exchange rates
     */
    public function warmUpCache(): void
    {
        $pairs = [
            ['USD', 'CAD'],
            ['CAD', 'USD'],
        ];
        
        foreach ($pairs as [$from, $to]) {
            $this->getExchangeRate($from, $to);
        }
        
        Log::info('Currency conversion cache warmed up');
    }
}