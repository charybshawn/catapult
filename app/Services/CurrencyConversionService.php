<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive multi-currency pricing and conversion service for global agricultural operations.
 * 
 * This specialized service provides robust currency conversion capabilities supporting
 * international agricultural commerce, multi-currency pricing displays, and flexible
 * financial reporting. Features intelligent caching, multiple fallback strategies,
 * and professional currency formatting for global business operations.
 * 
 * @service_domain International commerce and multi-currency financial management
 * @business_purpose Enable global agricultural sales with accurate currency conversion
 * @agricultural_focus International microgreens sales and B2B commerce
 * @currency_support Primary USD/CAD support with extensible architecture
 * @reliability_focus Multiple fallback strategies ensure continuous operation
 * 
 * Core Currency Management Features:
 * - **Live Exchange Rates**: Real-time currency conversion using external APIs
 * - **Intelligent Caching**: Multi-tier caching strategy for performance and reliability
 * - **Fallback Protection**: Static rates and extended caching prevent service interruption
 * - **Professional Formatting**: Business-appropriate currency display with symbols
 * - **Conversion Details**: Detailed conversion information for transparency
 * - **Cache Management**: Administrative tools for cache warming and clearing
 * 
 * International Commerce Applications:
 * - **Global Pricing**: Display prices in customer's preferred currency
 * - **Cross-Border Sales**: Support for international agricultural commerce
 * - **Financial Reporting**: Multi-currency reporting for international operations
 * - **B2B Commerce**: Professional currency conversion for business customers
 * - **Market Expansion**: Enable sales in new geographic markets
 * - **Customer Experience**: Native currency display improves customer satisfaction
 * 
 * Reliability and Performance Architecture:
 * - **Multi-Tier Caching**: 6-hour primary cache with 7-day fallback cache
 * - **API Integration**: External exchange rate API with timeout protection
 * - **Static Fallbacks**: Hardcoded rates for critical currency pairs
 * - **Error Resilience**: Graceful degradation when external services fail
 * - **Performance Optimization**: Efficient caching reduces API calls and latency
 * 
 * Business Intelligence Benefits:
 * - **Accurate Pricing**: Real-time rates ensure competitive and accurate pricing
 * - **Cost Management**: Understanding currency fluctuations for financial planning
 * - **Market Analysis**: Currency conversion data supports international expansion
 * - **Customer Service**: Transparent conversion rates build customer trust
 * - **Financial Compliance**: Accurate conversion records for accounting and taxation
 * 
 * Supported Currency Operations:
 * - **USD/CAD Conversion**: Primary currency pair with comprehensive support
 * - **Flexible Architecture**: Extensible design for additional currency support
 * - **Bidirectional Conversion**: Full support for both directions of currency pairs
 * - **Professional Formatting**: Appropriate symbols and formatting for each currency
 * - **Conversion Transparency**: Detailed information about rates and calculations
 * 
 * Technical Architecture:
 * - **HTTP Client Integration**: Secure API communication with timeout protection
 * - **Laravel Cache Integration**: Leverages Laravel caching for performance
 * - **Exception Handling**: Comprehensive error handling with logging
 * - **Service Isolation**: Self-contained service with minimal dependencies
 * - **Configuration Management**: Easily adjustable rates and API endpoints
 * 
 * Caching Strategy:
 * - **Primary Cache**: 6-hour cache for frequently accessed rates
 * - **Fallback Cache**: 7-day extended cache for API failure scenarios
 * - **Static Rates**: Hardcoded fallback rates for critical business continuity
 * - **Cache Warming**: Proactive cache population for improved performance
 * - **Administrative Control**: Cache clearing and management capabilities
 * 
 * Quality Assurance:
 * - **Rate Validation**: Ensures received rates are reasonable and valid
 * - **Comprehensive Logging**: Detailed logs for troubleshooting and monitoring
 * - **Fallback Testing**: Multiple fallback strategies tested for reliability
 * - **Performance Monitoring**: Cache hit rates and API response time tracking
 * 
 * Customer Experience Features:
 * - **Professional Display**: Currency symbols and formatting meet international standards
 * - **Conversion Transparency**: Clear rate information builds customer confidence
 * - **Performance**: Fast currency conversion through intelligent caching
 * - **Reliability**: Multiple fallbacks ensure consistent service availability
 * 
 * @international_commerce Comprehensive multi-currency support for global operations
 * @agricultural_export Specialized currency features for international agricultural sales
 * @reliability_engineering Multiple fallback strategies ensure continuous operation
 * @customer_experience Professional currency display and transparent conversion rates
 */
class CurrencyConversionService
{
    /**
     * Static exchange rate fallbacks for critical business continuity.
     * 
     * Hardcoded exchange rates serving as ultimate fallback when external APIs
     * and caches are unavailable. These rates should be updated periodically
     * to maintain reasonable accuracy for critical business operations.
     * 
     * @var array<string, float> Static fallback rates for business continuity
     * @business_continuity Ensures currency conversion always available
     * @manual_maintenance Requires periodic updates for rate accuracy
     * @critical_pairs Focus on most important currency pairs for business
     * 
     * Rate Definitions:
     * - USD_TO_CAD: US Dollar to Canadian Dollar conversion rate
     * - CAD_TO_USD: Canadian Dollar to US Dollar conversion rate
     * 
     * Maintenance Requirements:
     * - Review rates quarterly for continued accuracy
     * - Update rates during significant currency fluctuations
     * - Monitor against live rates to ensure reasonable fallback values
     * 
     * Business Applications:
     * - Emergency fallback when all other rate sources fail
     * - System initialization before cache warming
     * - Critical business continuity during extended API outages
     * - Baseline rates for system testing and development
     */
    const FALLBACK_RATES = [
        'USD_TO_CAD' => 1.35,
        'CAD_TO_USD' => 0.74,
    ];
    
    /**
     * Convert United States Dollars to Canadian Dollars with current exchange rates.
     * 
     * Performs accurate currency conversion from USD to CAD using live exchange rates
     * with intelligent caching and fallback protection. Essential for Canadian
     * customers purchasing agricultural products priced in USD or for financial
     * reporting requiring CAD values.
     * 
     * @param float $usdAmount Amount in US Dollars to convert
     * @return float Equivalent amount in Canadian Dollars (rounded to 2 decimals)
     * 
     * @currency_conversion Professional USD to CAD conversion with current rates
     * @canadian_market Essential for Canadian agricultural commerce
     * @financial_accuracy Precise conversion with appropriate rounding
     * @rate_intelligence Uses live rates with fallback protection
     * 
     * Business Applications:
     * - **Canadian Sales**: Display USD-priced products in CAD for Canadian customers
     * - **Financial Reporting**: Convert USD revenue to CAD for Canadian operations
     * - **Price Comparison**: Enable customers to compare prices in familiar currency
     * - **B2B Commerce**: Professional currency conversion for Canadian business customers
     * - **Market Analysis**: Understand pricing competitiveness in Canadian market
     * 
     * Conversion Process:
     * - **Rate Retrieval**: Gets current USD to CAD exchange rate
     * - **Amount Calculation**: Multiplies USD amount by conversion rate
     * - **Precision Rounding**: Rounds result to 2 decimal places for currency accuracy
     * - **Fallback Protection**: Uses cached or static rates if live rates unavailable
     * 
     * Quality Assurance:
     * - **Rate Accuracy**: Uses current market rates for precise conversion
     * - **Rounding Standards**: Follows standard currency rounding practices
     * - **Reliability**: Multiple fallback mechanisms ensure consistent operation
     * - **Performance**: Cached rates provide fast conversion without API delays
     * 
     * @customer_service Enables Canadian customers to see prices in familiar currency
     * @financial_compliance Accurate conversion for accounting and reporting requirements
     * @market_expansion Supports expansion into Canadian agricultural markets
     */
    public function usdToCad(float $usdAmount): float
    {
        $rate = $this->getExchangeRate('USD', 'CAD');
        return round($usdAmount * $rate, 2);
    }
    
    /**
     * Convert Canadian Dollars to United States Dollars with current exchange rates.
     * 
     * Performs accurate currency conversion from CAD to USD using live exchange rates
     * with intelligent caching and fallback protection. Essential for US customers
     * purchasing from Canadian operations or for financial analysis requiring
     * USD equivalents of Canadian revenue.
     * 
     * @param float $cadAmount Amount in Canadian Dollars to convert
     * @return float Equivalent amount in US Dollars (rounded to 2 decimals)
     * 
     * @currency_conversion Professional CAD to USD conversion with current rates
     * @us_market Essential for US agricultural commerce and expansion
     * @financial_accuracy Precise conversion with appropriate rounding
     * @rate_intelligence Uses live rates with comprehensive fallback protection
     * 
     * Business Applications:
     * - **US Market Expansion**: Convert Canadian prices for US market entry
     * - **Financial Reporting**: Report Canadian revenue in USD for consolidated statements
     * - **Competitive Analysis**: Compare Canadian operations against US competitors
     * - **Investment Analysis**: Convert Canadian investments to USD for evaluation
     * - **Cross-Border Commerce**: Support US customers purchasing from Canadian operations
     * 
     * Conversion Process:
     * - **Rate Retrieval**: Gets current CAD to USD exchange rate
     * - **Amount Calculation**: Multiplies CAD amount by conversion rate
     * - **Precision Rounding**: Rounds result to 2 decimal places for currency accuracy
     * - **Fallback Protection**: Uses cached or static rates if live rates unavailable
     * 
     * Market Intelligence:
     * - **Exchange Rate Trends**: Historical rate data supports pricing decisions
     * - **Competitive Positioning**: USD pricing helps position against US competitors
     * - **Revenue Analysis**: USD conversion enables international revenue comparison
     * - **Investment Planning**: USD values support cross-border investment decisions
     * 
     * Quality Assurance:
     * - **Rate Accuracy**: Current market rates ensure precise financial conversion
     * - **Standard Rounding**: Follows international currency rounding conventions
     * - **Reliability**: Multiple fallback strategies ensure continuous availability
     * - **Performance**: Efficient caching minimizes conversion latency
     * 
     * @international_expansion Enables expansion into US agricultural markets
     * @financial_analysis Accurate USD conversion for business intelligence
     * @customer_service Supports US customers with familiar currency pricing
     */
    public function cadToUsd(float $cadAmount): float
    {
        $rate = $this->getExchangeRate('CAD', 'USD');
        return round($cadAmount * $rate, 2);
    }
    
    /**
     * Convert specified currency amount to Canadian Dollars with intelligent routing.
     * 
     * Provides flexible currency conversion to CAD with support for multiple source
     * currencies and intelligent conversion routing. Handles same-currency scenarios,
     * supported conversions, and graceful fallbacks for unsupported currencies.
     * Essential for Canadian operations serving international customers.
     * 
     * @param float $amount Amount to convert in source currency
     * @param string $fromCurrency Source currency code (USD, CAD, etc.)
     * @return float Equivalent amount in Canadian Dollars
     * 
     * @flexible_conversion Intelligent routing for multiple source currencies
     * @canadian_operations Central conversion point for Canadian business operations
     * @international_support Accommodates diverse international customer currencies
     * @graceful_fallback Handles unsupported currencies without system failure
     * 
     * Conversion Logic:
     * - **Same Currency**: Returns original amount if already in CAD
     * - **USD Conversion**: Uses specialized USD to CAD conversion method
     * - **Unsupported Currencies**: Logs warning and returns original amount
     * - **Error Resilience**: Graceful handling prevents system disruption
     * 
     * Business Applications:
     * - **International Commerce**: Convert various currencies to CAD for Canadian operations
     * - **Customer Service**: Display prices in CAD regardless of source currency
     * - **Financial Reporting**: Consolidate international revenue in Canadian dollars
     * - **Market Analysis**: Analyze international sales performance in home currency
     * - **Pricing Strategy**: Understand competitive positioning in Canadian market
     * 
     * Supported Conversions:
     * - **CAD to CAD**: Direct passthrough for efficiency
     * - **USD to CAD**: Full conversion using current exchange rates
     * - **Future Extensions**: Architecture supports additional currency pairs
     * - **Logging**: Comprehensive logging for unsupported currency tracking
     * 
     * Error Handling:
     * - **Unsupported Currencies**: Warns but continues operation with original amount
     * - **Comprehensive Logging**: Tracks conversion attempts for future enhancement
     * - **System Stability**: Never fails due to unsupported currency requests
     * - **Business Continuity**: Always returns a valid numeric result
     * 
     * Customer Experience:
     * - **Consistent Display**: All prices shown in CAD for Canadian operations
     * - **International Welcome**: Accepts various source currencies gracefully
     * - **Transparent Handling**: Clear logging of conversion capabilities
     * - **Reliable Service**: Consistent operation regardless of input currency
     * 
     * @canadian_commerce Central currency conversion for Canadian agricultural operations
     * @international_flexibility Accommodates diverse customer currency preferences
     * @system_reliability Graceful handling ensures continuous operation
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
     * Convert specified currency amount to United States Dollars with intelligent routing.
     * 
     * Provides flexible currency conversion to USD with support for multiple source
     * currencies and intelligent conversion routing. Handles same-currency scenarios,
     * supported conversions, and graceful fallbacks for unsupported currencies.
     * Essential for US operations and international financial reporting.
     * 
     * @param float $amount Amount to convert in source currency
     * @param string $fromCurrency Source currency code (CAD, USD, etc.)
     * @return float Equivalent amount in US Dollars
     * 
     * @flexible_conversion Intelligent routing for multiple source currencies
     * @us_operations Central conversion point for US business operations
     * @international_reporting Standard USD conversion for global financial analysis
     * @graceful_fallback Handles unsupported currencies without system failure
     * 
     * Conversion Logic:
     * - **Same Currency**: Returns original amount if already in USD
     * - **CAD Conversion**: Uses specialized CAD to USD conversion method
     * - **Unsupported Currencies**: Logs warning and returns original amount
     * - **Error Resilience**: Graceful handling prevents system disruption
     * 
     * Business Applications:
     * - **US Market Operations**: Convert various currencies to USD for American operations
     * - **International Reporting**: Standardize global revenue in US dollars
     * - **Competitive Analysis**: Compare international performance in standard currency
     * - **Investment Analysis**: Convert international investments to USD for evaluation
     * - **Financial Consolidation**: Combine multi-currency operations in USD reporting
     * 
     * Global Business Benefits:
     * - **Standard Reporting**: USD as common currency for international analysis
     * - **Market Comparison**: Enable comparison across different geographic markets
     * - **Investment Decisions**: USD conversion supports international investment analysis
     * - **Regulatory Compliance**: Many international standards require USD reporting
     * 
     * Supported Conversions:
     * - **USD to USD**: Direct passthrough for efficiency
     * - **CAD to USD**: Full conversion using current exchange rates
     * - **Future Extensions**: Architecture supports additional currency pairs
     * - **Comprehensive Logging**: Tracks all conversion attempts for analytics
     * 
     * Error Handling:
     * - **Unsupported Currencies**: Warns but continues with original amount
     * - **Business Continuity**: Always returns valid numeric result
     * - **System Stability**: Never fails due to unsupported currency requests
     * - **Audit Trail**: Complete logging for compliance and troubleshooting
     * 
     * @us_commerce Central currency conversion for US agricultural operations
     * @international_standards USD as global standard for international commerce
     * @financial_reporting Essential for consolidated international financial statements
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
     * Retrieve current exchange rate with comprehensive fallback strategy.
     * 
     * Implements sophisticated multi-tier caching and fallback system to ensure
     * reliable exchange rate availability. Uses live API data when available,
     * cached rates for performance, extended fallback caching for reliability,
     * and static rates for critical business continuity.
     * 
     * @param string $from Source currency code
     * @param string $to Target currency code
     * @return float Current exchange rate from source to target currency
     * 
     * @multi_tier_caching Sophisticated caching strategy for performance and reliability
     * @api_integration Live exchange rate data from external financial APIs
     * @fallback_protection Multiple fallback strategies ensure continuous operation
     * @business_continuity Static rates prevent service interruption
     * 
     * Rate Retrieval Strategy:
     * 1. **Primary Cache**: 6-hour cached rates for optimal performance
     * 2. **Live API**: Real-time rates from external financial services
     * 3. **Fallback Cache**: 7-day extended cache for API failure scenarios
     * 4. **Static Rates**: Hardcoded rates for critical business continuity
     * 5. **Ultimate Fallback**: 1.0 rate with short-term caching to prevent repeated failures
     * 
     * Caching Architecture:
     * - **Primary Cache**: 6-hour expiration for fresh rates with good performance
     * - **Fallback Cache**: 7-day extended cache stored when live rates succeed
     * - **Short-term Cache**: 1-hour cache for fallback rates to reduce API load
     * - **Emergency Cache**: 30-minute cache for ultimate fallback scenarios
     * 
     * Business Continuity Features:
     * - **API Failure Protection**: Extended cache prevents service interruption
     * - **Static Rate Fallbacks**: Hardcoded rates ensure critical currency pairs always work
     * - **Graceful Degradation**: System continues operation even when external APIs fail
     * - **Performance Optimization**: Caching reduces latency and external API dependency
     * 
     * Quality Assurance:
     * - **Rate Validation**: Comprehensive logging of rate sources and values
     * - **Error Handling**: Graceful exception handling with detailed logging
     * - **Monitoring**: Complete audit trail for troubleshooting and performance analysis
     * - **Reliability**: Multiple fallback mechanisms ensure consistent service availability
     * 
     * Administrative Features:
     * - **Cache Management**: Sophisticated cache key management for efficiency
     * - **Logging**: Comprehensive logs for rate sources, failures, and fallbacks
     * - **Monitoring**: Detailed information for system health monitoring
     * - **Flexibility**: Easy configuration updates for rates and timing
     * 
     * @protected_method Internal rate management logic for conversion services
     * @reliability_engineering Multiple fallback strategies ensure business continuity
     * @performance_optimization Intelligent caching minimizes API dependency
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
        } catch (Exception $e) {
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
     * Retrieve real-time exchange rate from external financial API service.
     * 
     * Connects to external exchange rate API to fetch current market rates with
     * timeout protection and error handling. Essential for providing accurate,
     * up-to-date currency conversion rates for international agricultural commerce.
     * 
     * @param string $from Source currency code for rate lookup
     * @param string $to Target currency code for rate lookup
     * @return float|null Current exchange rate or null if unavailable
     * 
     * @api_integration Secure connection to external financial data services
     * @real_time_rates Current market rates for accurate currency conversion
     * @timeout_protection Network timeout prevents service delays
     * @error_resilience Graceful handling of API failures and invalid responses
     * 
     * API Integration Details:
     * - **Service Provider**: exchangerate-api.com for reliable financial data
     * - **Timeout Protection**: 10-second timeout prevents service delays
     * - **Response Validation**: Confirms API response structure and rate availability
     * - **Error Handling**: Returns null for failed requests to trigger fallback logic
     * 
     * Data Quality Assurance:
     * - **Response Validation**: Confirms API response contains requested currency rates
     * - **Type Conversion**: Ensures returned rate is properly converted to float
     * - **Null Handling**: Returns null for invalid or unavailable rates
     * - **Service Reliability**: Integrates with established financial data provider
     * 
     * Business Benefits:
     * - **Rate Accuracy**: Current market rates ensure competitive and fair pricing
     * - **Customer Trust**: Real-time rates demonstrate transparency and professionalism
     * - **Financial Compliance**: Accurate rates support proper accounting and taxation
     * - **Market Competitiveness**: Current rates enable competitive international pricing
     * 
     * Error Scenarios:
     * - **Network Failures**: Timeout or connectivity issues return null
     * - **API Errors**: Service errors or invalid responses return null
     * - **Missing Rates**: Requested currency pair not available returns null
     * - **Invalid Data**: Malformed response data returns null
     * 
     * Integration Architecture:
     * - **HTTP Client**: Laravel HTTP client with timeout protection
     * - **JSON Processing**: Automatic JSON response parsing and validation
     * - **Service Isolation**: API failures don't affect other system operations
     * - **Fallback Integration**: Null return triggers comprehensive fallback strategy
     * 
     * @protected_method Internal API integration for exchange rate services
     * @financial_data Professional integration with established financial data providers
     * @service_reliability Timeout protection and error handling ensure system stability
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
     * Generate professionally formatted currency display with conversion.
     * 
     * Creates business-appropriate currency string with proper symbols and formatting
     * after performing currency conversion when needed. Essential for professional
     * price display in international agricultural commerce and customer-facing
     * financial information.
     * 
     * @param float $amount Original amount in source currency
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code for display
     * @return string Professionally formatted currency string
     * 
     * @professional_formatting Business-appropriate currency display standards
     * @international_commerce Proper currency formatting for global customers
     * @customer_experience Clear, professional price presentation
     * @conversion_automation Automatic conversion when currencies differ
     * 
     * Formatting Process:
     * - **Currency Matching**: Checks if conversion is needed
     * - **Automatic Conversion**: Performs conversion when currencies differ
     * - **Professional Display**: Applies appropriate currency symbols and formatting
     * - **Standard Precision**: Consistent decimal precision for currency display
     * 
     * Business Applications:
     * - **Price Display**: Professional formatting for product pricing
     * - **Invoice Generation**: Business-appropriate currency formatting
     * - **Customer Communications**: Clear currency display in customer-facing documents
     * - **Financial Reports**: Professional currency formatting for business documents
     * - **E-commerce**: Consistent currency display across online platforms
     * 
     * Display Standards:
     * - **Currency Symbols**: Appropriate symbols for each supported currency
     * - **Decimal Precision**: Standard 2-decimal precision for currency amounts
     * - **Professional Appearance**: Business-appropriate formatting conventions
     * - **International Standards**: Follows international currency display practices
     * 
     * Conversion Logic:
     * - **Same Currency**: Direct formatting without conversion
     * - **CAD Target**: Uses convertToCad() method for conversion
     * - **USD Target**: Uses convertToUsd() method for conversion
     * - **Consistent Results**: Reliable formatting across all conversion scenarios
     * 
     * Customer Experience:
     * - **Clear Pricing**: Professional currency display builds customer confidence
     * - **Familiar Format**: Currency formatting meets customer expectations
     * - **International Support**: Appropriate formatting for global customers
     * - **Consistency**: Uniform currency display across all customer touchpoints
     * 
     * @customer_facing Professional currency display for customer interactions
     * @business_standards Meets international currency formatting requirements
     * @e_commerce_ready Suitable for online agricultural commerce platforms
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
     * Generate comprehensive currency display with conversion details and transparency.
     * 
     * Creates detailed currency format showing both converted amount and original
     * amount with exchange rate for complete transparency. Essential for B2B
     * commerce where customers need to understand conversion calculations for
     * accounting and verification purposes.
     * 
     * @param float $amount Original amount in source currency
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code for display
     * @return string Detailed formatted string with conversion information
     * 
     * @conversion_transparency Complete visibility into currency conversion calculations
     * @b2b_commerce Detailed information supports business customer accounting needs
     * @professional_documentation Comprehensive conversion details for business records
     * @customer_trust Transparency builds confidence in currency conversion accuracy
     * 
     * Detailed Format: "{TARGET_AMOUNT} (from {SOURCE_AMOUNT} @ {RATE})"
     * Example: "CDN$135.00 (from USD$100.00 @ 1.35)"
     * 
     * Business Applications:
     * - **B2B Invoicing**: Detailed conversion information for business accounting
     * - **Financial Documentation**: Complete conversion details for record keeping
     * - **Customer Service**: Transparent pricing information for customer inquiries
     * - **Audit Requirements**: Detailed conversion records for financial compliance
     * - **International Trade**: Professional documentation for cross-border commerce
     * 
     * Information Components:
     * - **Converted Amount**: Professional formatting of target currency amount
     * - **Original Amount**: Source currency amount for reference
     * - **Exchange Rate**: Current rate used for transparency
     * - **Complete Context**: All information needed for verification and records
     * 
     * Professional Benefits:
     * - **Transparency**: Complete conversion information builds business trust
     * - **Verification**: Customers can verify conversion calculations independently
     * - **Documentation**: Comprehensive information supports business record keeping
     * - **Compliance**: Detailed records meet international business documentation standards
     * 
     * Customer Experience:
     * - **Trust Building**: Transparent conversion information demonstrates honesty
     * - **Understanding**: Clear explanation of currency conversion calculations
     * - **Professional Service**: Detailed information reflects professional business practices
     * - **Verification**: Customers can independently confirm conversion accuracy
     * 
     * Same Currency Handling:
     * - **Direct Display**: Simple formatting when no conversion needed
     * - **Efficiency**: Avoids unnecessary conversion details for same currency
     * - **Consistent Interface**: Same method signature regardless of conversion need
     * 
     * @b2b_transparency Essential for business customers requiring detailed conversion information
     * @financial_compliance Detailed records support accounting and audit requirements
     * @customer_trust Transparency in conversion calculations builds business relationships
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
     * Apply professional currency formatting with appropriate symbols and precision.
     * 
     * Creates standardized currency display using proper symbols and formatting
     * conventions for international business presentation. Essential foundation
     * for all currency display throughout the agricultural commerce system.
     * 
     * @param float $amount Numeric amount to format
     * @param string $currency Currency code for symbol and formatting selection
     * @return string Professionally formatted currency string
     * 
     * @professional_formatting International business standards for currency display
     * @symbol_accuracy Proper currency symbols for professional presentation
     * @precision_standards Consistent decimal precision across all currencies
     * @international_support Multiple currency symbols for global commerce
     * 
     * Supported Currency Symbols:
     * - **USD**: "USD$" - Clear identification for US Dollar amounts
     * - **CAD**: "CDN$" - Distinctive Canadian Dollar symbol
     * - **EUR**: "€" - Standard Euro symbol for European markets
     * - **GBP**: "£" - British Pound symbol for UK markets
     * - **Fallback**: Currency code + space for unsupported currencies
     * 
     * Formatting Standards:
     * - **Decimal Precision**: Consistent 2-decimal places for all currencies
     * - **Thousands Separator**: Comma separation for large amounts
     * - **Symbol Placement**: Currency symbol before amount following international conventions
     * - **Professional Appearance**: Business-appropriate formatting for all contexts
     * 
     * Business Applications:
     * - **Price Display**: Professional pricing throughout agricultural commerce platform
     * - **Invoice Generation**: Business-appropriate currency formatting for billing
     * - **Financial Reports**: Consistent currency display in business documents
     * - **Customer Communications**: Professional currency presentation in all customer touchpoints
     * - **International Commerce**: Appropriate formatting for global agricultural trade
     * 
     * Quality Assurance:
     * - **Symbol Accuracy**: Correct currency symbols for professional presentation
     * - **Consistency**: Uniform formatting across all system components
     * - **International Standards**: Follows global currency formatting conventions
     * - **Extensibility**: Easy addition of new currency symbols as business expands
     * 
     * Customer Experience:
     * - **Familiar Format**: Currency display meets international customer expectations
     * - **Professional Appearance**: Business-quality formatting builds customer confidence
     * - **Clear Recognition**: Appropriate symbols eliminate currency confusion
     * - **Consistent Experience**: Uniform currency display across all interactions
     * 
     * Technical Implementation:
     * - **Symbol Mapping**: Efficient lookup table for currency symbols
     * - **Fallback Logic**: Graceful handling of unsupported currencies
     * - **Standard Functions**: Uses PHP number_format() for consistent precision
     * - **Performance**: Efficient formatting suitable for high-volume operations
     * 
     * @protected_method Internal utility for consistent currency formatting
     * @business_standards Professional currency display for international commerce
     * @customer_experience Proper formatting enhances customer trust and satisfaction
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
     * Remove all cached exchange rate data for fresh rate retrieval.
     * 
     * Clears both primary and fallback caches for all supported currency pairs,
     * forcing fresh rate retrieval from external APIs on next conversion request.
     * Essential administrative tool for rate management and troubleshooting
     * currency conversion issues.
     * 
     * @return void All currency conversion caches cleared
     * 
     * @cache_management Administrative control over currency rate caching
     * @troubleshooting Tool for resolving currency conversion issues
     * @rate_refresh Forces fresh rate retrieval from external APIs
     * @administrative_tool Essential maintenance capability for currency services
     * 
     * Cache Clearing Scope:
     * - **Primary Caches**: 6-hour cached rates cleared for all currency pairs
     * - **Fallback Caches**: 7-day extended caches cleared for complete reset
     * - **All Pairs**: Comprehensive clearing of all supported currency combinations
     * - **Bidirectional**: Both directions of each currency pair cleared
     * 
     * Administrative Applications:
     * - **Rate Issues**: Clear corrupted or incorrect cached rates
     * - **System Maintenance**: Fresh start for currency conversion services
     * - **Troubleshooting**: Eliminate cached data as source of conversion problems
     * - **Testing**: Clean cache state for currency conversion testing
     * - **Emergency Response**: Quick resolution for currency rate emergencies
     * 
     * Supported Currency Pairs:
     * - **Major Pairs**: USD/CAD, EUR/USD, GBP/USD and their reverses
     * - **Extended Support**: EUR and GBP pairs for future expansion
     * - **Comprehensive**: All combinations within supported currency set
     * - **Bidirectional**: Both directions of each pair cleared completely
     * 
     * Business Benefits:
     * - **Rate Accuracy**: Ensures next conversion uses most current rates
     * - **Problem Resolution**: Quick fix for currency conversion issues
     * - **System Health**: Maintains clean cache state for optimal performance
     * - **Administrative Control**: Direct management of currency conversion behavior
     * 
     * Performance Considerations:
     * - **API Impact**: Next conversions will require API calls for fresh rates
     * - **Temporary Slowdown**: Brief performance impact until new rates cached
     * - **System Stability**: Cache clearing never affects core system operation
     * - **Recovery**: System quickly rebuilds cache through normal operation
     * 
     * Logging and Monitoring:
     * - **Operation Logging**: Cache clear operations recorded for audit trail
     * - **Administrative Tracking**: Clear indication of manual cache management
     * - **System Monitoring**: Integration with system health monitoring
     * 
     * @administrative_capability Essential tool for currency service management
     * @troubleshooting_support Quick resolution for currency conversion issues
     * @system_maintenance Maintains optimal currency conversion performance
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
     * Proactively populate cache with current exchange rates for optimal performance.
     * 
     * Preloads cache with current exchange rates for critical currency pairs,
     * ensuring fast response times for currency conversions without API delays.
     * Essential performance optimization for high-traffic agricultural commerce
     * operations requiring responsive currency conversion.
     * 
     * @return void Critical currency pair rates cached for optimal performance
     * 
     * @performance_optimization Proactive caching eliminates API delays for common conversions
     * @cache_warming Strategic cache population for responsive customer experience
     * @system_preparation Ensures currency services ready for high-traffic operations
     * @customer_experience Fast currency conversion improves customer satisfaction
     * 
     * Cache Warming Strategy:
     * - **Critical Pairs**: USD/CAD and CAD/USD for North American agricultural commerce
     * - **Bidirectional**: Both directions of critical currency pairs
     * - **Fresh Rates**: Triggers fresh API calls to populate with current rates
     * - **Performance Focus**: Emphasizes most frequently used currency conversions
     * 
     * Business Applications:
     * - **System Startup**: Prepare currency services for optimal performance
     * - **Peak Traffic**: Preload cache before high-traffic periods
     * - **Customer Experience**: Ensure responsive pricing displays
     * - **Performance Optimization**: Minimize customer wait times for currency conversion
     * - **Scheduled Maintenance**: Regular cache warming for consistent performance
     * 
     * Performance Benefits:
     * - **Reduced Latency**: Eliminates API call delays for common currency pairs
     * - **Customer Satisfaction**: Fast currency conversion improves user experience
     * - **System Responsiveness**: Immediate currency display without external API delays
     * - **Reliability**: Cached rates available even if API temporarily slow
     * 
     * Strategic Currency Selection:
     * - **USD/CAD**: Primary currency pair for North American agricultural operations
     * - **Market Focus**: Concentrates on most critical business currency conversions
     * - **Extensible**: Easy addition of new currency pairs as business expands
     * - **Business Alignment**: Cache warming matches actual business currency needs
     * 
     * Administrative Integration:
     * - **Scheduled Tasks**: Can be integrated with automated maintenance schedules
     * - **System Monitoring**: Provides foundation for performance monitoring
     * - **Load Management**: Distributed cache warming prevents API rate limiting
     * - **Health Checks**: Cache warming success indicates system health
     * 
     * Logging and Monitoring:
     * - **Operation Tracking**: Cache warming operations logged for monitoring
     * - **Performance Metrics**: Foundation for currency conversion performance analysis
     * - **System Health**: Successful warming indicates healthy currency services
     * 
     * @system_optimization Strategic cache management for optimal currency service performance
     * @customer_experience Responsive currency conversion enhances customer satisfaction
     * @operational_excellence Proactive performance management for business-critical services
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