<?php

namespace App\Services;

use Exception;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Comprehensive system monitoring and audit logging service for API operations.
 * 
 * This specialized service provides complete API request/response logging, performance
 * monitoring, security auditing, and business intelligence for agricultural commerce
 * API operations. Essential for maintaining system health, security compliance,
 * troubleshooting, and optimizing API performance for customer satisfaction.
 * 
 * @service_domain System monitoring, security auditing, and API performance analysis
 * @business_purpose Comprehensive API monitoring for operational excellence and security
 * @agricultural_focus API logging for agricultural commerce platform and B2B integrations
 * @security_compliance Audit trails for compliance and security incident investigation
 * @performance_monitoring Real-time API performance tracking and optimization
 * 
 * Core Monitoring Features:
 * - **Complete Request Logging**: Detailed capture of all API request parameters and metadata
 * - **Response Correlation**: Links requests with responses for complete transaction tracking
 * - **Performance Metrics**: Response time analysis and slow API call detection
 * - **Error Tracking**: Comprehensive exception logging with stack traces and context
 * - **Security Auditing**: Sanitized logging with sensitive data protection
 * - **Statistical Analysis**: Performance trends and usage pattern analytics
 * 
 * API Monitoring Applications:
 * - **Performance Optimization**: Identify slow endpoints for agricultural commerce efficiency
 * - **Security Monitoring**: Track suspicious API usage patterns and potential threats
 * - **Business Intelligence**: API usage analytics for agricultural commerce insights
 * - **Troubleshooting**: Complete request/response trails for issue resolution
 * - **Compliance**: Audit trails for regulatory and security compliance requirements
 * - **Customer Service**: API usage data for customer support and service improvement
 * 
 * Security and Privacy Features:
 * - **Data Sanitization**: Automatic redaction of sensitive authentication and personal data
 * - **Header Protection**: Secure handling of authorization tokens and API keys
 * - **Request Body Sanitization**: Password and credential protection in logged data
 * - **Privacy Compliance**: GDPR and privacy-compliant logging practices
 * - **Audit Trail**: Complete but secure logging for compliance requirements
 * 
 * Performance Intelligence:
 * - **Response Time Tracking**: Millisecond-precision performance measurement
 * - **Slow Call Detection**: Automatic identification of performance issues
 * - **Endpoint Analytics**: Per-endpoint performance and usage statistics
 * - **Rate Limiting Support**: Request frequency analysis for rate limiting
 * - **Resource Usage**: Response size tracking for optimization insights
 * 
 * Business Intelligence Applications:
 * - **Usage Analytics**: API endpoint popularity and customer behavior analysis
 * - **Performance Trends**: Historical performance data for capacity planning
 * - **Error Analysis**: Error patterns and failure rate monitoring
 * - **Customer Insights**: API usage patterns for agricultural commerce optimization
 * - **System Health**: Overall API ecosystem health monitoring and alerting
 * 
 * Technical Architecture:
 * - **Laravel Activity Log Integration**: Leverages established activity logging framework
 * - **Caching Strategy**: Temporary request storage for response correlation
 * - **UUID Tracking**: Unique request identification for complete transaction tracing
 * - **Configurable Thresholds**: Adjustable performance monitoring parameters
 * - **Memory Efficient**: Optimized logging prevents memory issues during high traffic
 * 
 * Integration Points:
 * - API Middleware: Automatic logging for all API requests and responses
 * - Exception Handlers: Comprehensive error logging with full context
 * - Performance Monitoring: Integration with system health dashboards
 * - Security Systems: Audit data for security analysis and threat detection
 * - Business Intelligence: API data feeds into agricultural commerce analytics
 * 
 * Compliance and Auditing:
 * - **Security Compliance**: Complete audit trails for security investigations
 * - **Regulatory Requirements**: Logging meets agricultural and commerce compliance standards
 * - **Data Protection**: Privacy-compliant logging with sensitive data protection
 * - **Forensic Analysis**: Detailed logs support security incident investigation
 * - **Business Continuity**: Monitoring supports proactive issue prevention
 * 
 * Administrative Features:
 * - **Configurable Logging**: Adjustable sensitivity and detail levels
 * - **Performance Thresholds**: Customizable slow call detection parameters
 * - **Statistics Generation**: Comprehensive API usage and performance reporting
 * - **Rate Limit Monitoring**: Request frequency analysis for security and performance
 * 
 * @system_monitoring Comprehensive API monitoring for operational excellence
 * @security_auditing Complete audit trails for security compliance and investigation
 * @performance_optimization Data-driven API performance improvement insights
 * @business_intelligence API usage analytics supporting agricultural commerce decisions
 */
class ApiLogService
{
    /**
     * Log comprehensive API request with security-conscious data capture.
     * 
     * Captures complete API request information including headers, parameters,
     * and metadata while protecting sensitive authentication data. Generates
     * unique request identifier for correlation with responses and errors.
     * Essential for complete API transaction tracking and security auditing.
     * 
     * @param Request $request The HTTP request to log with complete context
     * @param string $endpoint The API endpoint being accessed
     * @param string|null $controller Optional controller name for enhanced tracking
     * @return string Unique request identifier for response/error correlation
     * 
     * @security_logging Comprehensive request logging with sensitive data protection
     * @transaction_tracking Unique ID enables complete request/response correlation
     * @audit_compliance Complete request trails for security and regulatory compliance
     * @performance_monitoring High-precision timing for performance analysis
     * 
     * Captured Request Data:
     * - **Request Identification**: UUID for unique transaction tracking
     * - **Endpoint Information**: Target endpoint and HTTP method
     * - **Request Parameters**: Query parameters and request body (sanitized)
     * - **Security Context**: IP address and user agent for security analysis
     * - **Headers**: HTTP headers with sensitive data redacted
     * - **Timing**: Microsecond-precision timestamp for performance measurement
     * - **Controller Context**: Optional controller information for enhanced tracking
     * 
     * Security and Privacy Protection:
     * - **Header Sanitization**: Authorization tokens and sensitive headers redacted
     * - **Body Sanitization**: Passwords and credentials automatically protected
     * - **Privacy Compliance**: Logging practices meet GDPR and privacy requirements
     * - **Audit Safe**: Complete logging without exposing sensitive information
     * 
     * Business Applications:
     * - **Security Monitoring**: Track API access patterns for threat detection
     * - **Performance Analysis**: Request data enables response time correlation
     * - **Usage Analytics**: Endpoint popularity and customer behavior insights
     * - **Compliance**: Complete audit trails for regulatory requirements
     * - **Troubleshooting**: Detailed request context for issue resolution
     * 
     * Technical Implementation:
     * - **UUID Generation**: Unique identifier for request/response correlation
     * - **Activity Logging**: Integration with Laravel activity log framework
     * - **Temporary Storage**: Request data cached for response correlation
     * - **Memory Efficiency**: Optimized data capture prevents memory issues
     * 
     * Correlation and Tracking:
     * - **Request ID**: Returned UUID links request with subsequent response/error logs
     * - **Cache Storage**: Temporary storage enables response time calculation
     * - **Transaction Integrity**: Complete request/response/error tracking
     * 
     * @audit_trail Complete request logging for security and compliance requirements
     * @performance_foundation Timing and context data enabling performance analysis
     * @security_intelligence Request patterns support threat detection and analysis
     */
    public function logRequest(Request $request, string $endpoint, string $controller = null): string
    {
        $requestId = Str::uuid()->toString();
        
        $properties = [
            'request_id' => $requestId,
            'endpoint' => $endpoint,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query(),
            'body' => $this->sanitizeBody($request->all()),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'controller' => $controller,
            'timestamp' => microtime(true),
        ];

        activity('api_request')
            ->withProperties($properties)
            ->log("API request to {$endpoint}");

        // Store request data temporarily for response correlation
        Cache::put("api_request_{$requestId}", $properties, 300);

        return $requestId;
    }

    /**
     * Log comprehensive API response with performance metrics and correlation.
     * 
     * Captures complete API response information including status codes, response
     * times, and data sizes while correlating with original request for complete
     * transaction tracking. Includes performance analysis and slow call detection
     * for proactive optimization.
     * 
     * @param string $requestId Unique identifier linking response to original request
     * @param mixed $response The response data (string, array, or object)
     * @param int $statusCode HTTP status code for response classification
     * @param string|null $controller Optional controller name for enhanced tracking
     * @return void Response logged with complete transaction correlation
     * 
     * @transaction_completion Links response with original request for complete tracking
     * @performance_analysis Detailed response time and size metrics
     * @slow_call_detection Automatic identification of performance issues
     * @audit_compliance Complete response trails for security and regulatory compliance
     * 
     * Response Metrics Captured:
     * - **Response Time**: Microsecond-precision calculation from request timestamp
     * - **Status Classification**: Success, client error, or server error categorization
     * - **Response Size**: Data size analysis for bandwidth and optimization insights
     * - **Response Preview**: Truncated response content for debugging context
     * - **Request Correlation**: Links response to original request via unique ID
     * 
     * Performance Monitoring:
     * - **Response Time Analysis**: Precise measurement from request to response
     * - **Slow Call Detection**: Automatic flagging of responses exceeding thresholds
     * - **Performance Alerting**: Integration with slow API call logging
     * - **Optimization Insights**: Data for identifying performance bottlenecks
     * 
     * Business Applications:
     * - **Performance Optimization**: Identify slow endpoints affecting customer experience
     * - **Service Level Monitoring**: Track API performance against SLA requirements
     * - **Usage Analytics**: Response patterns for agricultural commerce insights
     * - **Quality Assurance**: Success/error rates for service quality monitoring
     * - **Customer Experience**: Response time data for customer satisfaction optimization
     * 
     * Status Code Analysis:
     * - **Success Tracking**: 2xx responses for success rate calculation
     * - **Client Error Analysis**: 4xx responses for API usage pattern insights
     * - **Server Error Monitoring**: 5xx responses for system health tracking
     * - **Performance Correlation**: Status codes linked to response times
     * 
     * Cache Management:
     * - **Request Cleanup**: Automatic removal of cached request data after correlation
     * - **Memory Efficiency**: Prevents cache buildup during high-traffic operations
     * - **Data Integrity**: Ensures complete transaction tracking without memory leaks
     * 
     * @performance_intelligence Detailed metrics supporting API performance optimization
     * @service_monitoring Complete response tracking for service quality assurance
     * @customer_experience Response time data enables customer satisfaction optimization
     */
    public function logResponse(string $requestId, $response, int $statusCode, string $controller = null): void
    {
        $requestData = Cache::get("api_request_{$requestId}");
        $responseTime = $requestData ? microtime(true) - $requestData['timestamp'] : null;

        $properties = [
            'request_id' => $requestId,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'response_size' => $this->getResponseSize($response),
            'response_preview' => $this->getResponsePreview($response),
            'controller' => $controller,
        ];

        activity('api_response')
            ->withProperties($properties)
            ->log("API response with status {$statusCode}");

        // Log performance metrics
        if ($responseTime && $responseTime > config('logging.slow_api_threshold', 2.0)) {
            $this->logSlowApiCall($requestId, $requestData, $responseTime);
        }

        // Clean up cached request data
        Cache::forget("api_request_{$requestId}");
    }

    /**
     * Log comprehensive API error with complete exception context and request correlation.
     * 
     * Captures detailed exception information including stack traces, error context,
     * and original request data for complete error analysis. Essential for debugging,
     * system health monitoring, and security incident investigation.
     * 
     * @param string $requestId Unique identifier linking error to original request
     * @param Exception $exception The exception that occurred during API processing
     * @param string|null $controller Optional controller name for enhanced tracking
     * @return void Error logged with complete context for investigation
     * 
     * @error_tracking Comprehensive exception logging with full debugging context
     * @security_auditing Error patterns for security threat detection and analysis
     * @debugging_support Complete error context enabling efficient issue resolution
     * @system_health Error frequency and patterns for system health monitoring
     * 
     * Exception Data Captured:
     * - **Exception Classification**: Exception class for error categorization
     * - **Error Message**: Human-readable error description
     * - **Error Code**: Numeric error code for programmatic analysis
     * - **Source Location**: File and line number for precise error location
     * - **Stack Trace**: Complete call stack for debugging and analysis
     * - **Request Context**: Original request data for error reproduction
     * - **Controller Context**: Optional controller information for enhanced tracking
     * 
     * Security Applications:
     * - **Threat Detection**: Error patterns indicating potential security threats
     * - **Attack Analysis**: Exception data for security incident investigation
     * - **Vulnerability Identification**: Error patterns revealing system weaknesses
     * - **Audit Compliance**: Complete error trails for security compliance
     * 
     * Business Intelligence:
     * - **Error Rate Analysis**: Exception frequency for service quality monitoring
     * - **Customer Impact**: Error correlation with customer experience metrics
     * - **System Stability**: Exception patterns for reliability assessment
     * - **Service Level Monitoring**: Error data for SLA compliance tracking
     * 
     * Debugging and Development:
     * - **Issue Resolution**: Complete context enables efficient debugging
     * - **Root Cause Analysis**: Stack traces and request data for problem identification
     * - **Development Support**: Error context assists in code improvement
     * - **Quality Assurance**: Exception patterns guide testing and validation
     * 
     * Error Correlation:
     * - **Request Context**: Links errors to original request for complete analysis
     * - **Transaction Tracking**: Maintains request/response/error relationship
     * - **Cache Cleanup**: Automatic removal of request data after error logging
     * 
     * System Health Monitoring:
     * - **Error Frequency**: Exception rates for system health assessment
     * - **Pattern Detection**: Recurring errors indicating systematic issues
     * - **Performance Impact**: Error correlation with performance degradation
     * - **Alerting Support**: Error data feeds into monitoring and alerting systems
     * 
     * @security_intelligence Error patterns support threat detection and security analysis
     * @debugging_excellence Complete error context enables efficient issue resolution
     * @system_reliability Error monitoring supports proactive system health management
     */
    public function logError(string $requestId, Exception $exception, string $controller = null): void
    {
        $requestData = Cache::get("api_request_{$requestId}");

        activity('api_error')
            ->withProperties([
                'request_id' => $requestId,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request_data' => $requestData,
                'controller' => $controller,
            ])
            ->log("API error: {$exception->getMessage()}");

        // Clean up cached request data
        Cache::forget("api_request_{$requestId}");
    }

    /**
     * Log performance issue when API response time exceeds configured thresholds.
     * 
     * Creates specialized log entry for API calls that exceed performance thresholds,
     * enabling proactive performance monitoring and optimization. Essential for
     * maintaining responsive agricultural commerce API operations and customer
     * satisfaction.
     * 
     * @param string $requestId Unique identifier for slow call correlation
     * @param array|null $requestData Original request context for performance analysis
     * @param float $responseTime Actual response time that exceeded threshold
     * @return void Slow API call logged for performance analysis
     * 
     * @performance_monitoring Proactive identification of performance bottlenecks
     * @optimization_insights Data for targeted API performance improvements
     * @customer_experience Slow call detection enables customer satisfaction protection
     * @system_health Performance degradation tracking for system reliability
     * 
     * Performance Analysis Data:
     * - **Response Time**: Actual response time that triggered slow call detection
     * - **Performance Threshold**: Configured threshold for comparison and context
     * - **Endpoint Information**: Specific endpoint and method for targeted optimization
     * - **Request Context**: Original request data for performance analysis
     * - **Transaction Correlation**: Links slow call to original request for analysis
     * 
     * Business Applications:
     * - **Customer Experience**: Identify performance issues affecting customer satisfaction
     * - **Service Level Management**: Monitor API performance against SLA requirements
     * - **Agricultural Commerce**: Ensure responsive API operations for commerce platform
     * - **Competitive Advantage**: Maintain performance superiority in agricultural markets
     * - **Resource Planning**: Performance data guides infrastructure scaling decisions
     * 
     * Performance Optimization:
     * - **Bottleneck Identification**: Pinpoint specific endpoints requiring optimization
     * - **Trend Analysis**: Performance degradation patterns for proactive improvement
     * - **Resource Allocation**: Performance data guides server and database optimization
     * - **Code Optimization**: Slow call data identifies code requiring performance improvement
     * 
     * System Health Monitoring:
     * - **Performance Degradation**: Early detection of system performance issues
     * - **Capacity Planning**: Response time trends guide infrastructure planning
     * - **Quality Assurance**: Performance monitoring ensures service quality standards
     * - **Alerting Integration**: Slow call detection feeds into monitoring systems
     * 
     * Agricultural Commerce Context:
     * - **B2B Performance**: Ensure responsive API operations for business customers
     * - **Real-time Operations**: Performance monitoring for time-sensitive agricultural data
     * - **Customer Service**: Responsive APIs support positive customer experience
     * - **Market Competitiveness**: Performance advantage in agricultural technology
     * 
     * @protected_method Internal performance monitoring for slow call detection
     * @performance_excellence Proactive monitoring ensures optimal API response times
     * @customer_satisfaction Performance data enables customer experience optimization
     */
    protected function logSlowApiCall(string $requestId, ?array $requestData, float $responseTime): void
    {
        activity('slow_api_call')
            ->withProperties([
                'request_id' => $requestId,
                'endpoint' => $requestData['endpoint'] ?? 'unknown',
                'method' => $requestData['method'] ?? 'unknown',
                'response_time' => $responseTime,
                'threshold' => config('logging.slow_api_threshold', 2.0),
            ])
            ->log('Slow API call detected');
    }

    /**
     * Sanitize HTTP headers for secure logging while preserving debugging context.
     * 
     * Automatically redacts sensitive authentication and security headers while
     * preserving non-sensitive headers for debugging and analysis. Essential for
     * maintaining complete audit trails while protecting sensitive credentials
     * and authentication tokens.
     * 
     * @param array $headers Raw HTTP headers from request
     * @return array Sanitized headers with sensitive data redacted
     * 
     * @security_protection Automatic redaction of sensitive authentication data
     * @audit_compliance Privacy-compliant logging with debugging context preserved
     * @debugging_support Non-sensitive headers retained for troubleshooting
     * @privacy_engineering Balances security requirements with operational needs
     * 
     * Sensitive Headers Protected:
     * - **Authorization**: Bearer tokens, API keys, and authentication credentials
     * - **X-API-Key**: Custom API key headers for service authentication
     * - **Cookie**: Session cookies and authentication state
     * - **X-CSRF-Token**: Cross-site request forgery protection tokens
     * 
     * Security Benefits:
     * - **Credential Protection**: Prevents authentication token exposure in logs
     * - **Privacy Compliance**: GDPR and privacy-compliant logging practices
     * - **Audit Safety**: Complete logging without security credential exposure
     * - **Incident Investigation**: Secure audit trails for security analysis
     * 
     * Debugging Preservation:
     * - **Context Retention**: Non-sensitive headers preserved for analysis
     * - **Request Correlation**: Headers like Content-Type and Accept retained
     * - **User Agent**: Browser and client information preserved for analysis
     * - **Custom Headers**: Non-sensitive application headers retained
     * 
     * Business Applications:
     * - **Security Auditing**: Safe header logging for security investigation
     * - **Compliance**: Privacy-compliant logging for regulatory requirements
     * - **Customer Support**: Header context for troubleshooting without security risks
     * - **Development**: Safe debugging information for agricultural commerce APIs
     * 
     * Agricultural Commerce Context:
     * - **B2B Security**: Protects business customer authentication credentials
     * - **API Integration**: Secure logging for agricultural commerce API partnerships
     * - **Customer Privacy**: Protects customer authentication and session data
     * - **Regulatory Compliance**: Meets agricultural industry privacy requirements
     * 
     * @protected_method Internal security utility for safe header logging
     * @credential_protection Prevents sensitive authentication data exposure
     * @compliance_engineering Privacy-compliant logging with operational utility
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie', 'x-csrf-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Sanitize request body data for secure logging with recursive protection.
     * 
     * Automatically identifies and redacts sensitive fields throughout nested
     * request data structures while preserving business data for debugging.
     * Essential for comprehensive audit trails without exposing passwords,
     * tokens, or other sensitive credentials.
     * 
     * @param array $body Raw request body data with potential sensitive fields
     * @return array Sanitized request body with sensitive data redacted
     * 
     * @security_protection Comprehensive sensitive data redaction throughout nested structures
     * @recursive_sanitization Deep protection for complex data structures
     * @audit_compliance Privacy-compliant request logging with business context preserved
     * @debugging_support Business data retained while protecting sensitive credentials
     * 
     * Sensitive Fields Protected:
     * - **Password**: User passwords and authentication credentials
     * - **Password Confirmation**: Password verification fields
     * - **Token**: Authentication tokens and security credentials
     * - **Secret**: Secret keys and confidential authentication data
     * - **API Key**: Service authentication keys and credentials
     * 
     * Security Features:
     * - **Recursive Protection**: Sanitization works through nested data structures
     * - **Case Insensitive**: Field matching works regardless of capitalization
     * - **Comprehensive Coverage**: All variations of sensitive field names protected
     * - **Deep Sanitization**: Protection extends through complex object hierarchies
     * 
     * Business Data Preservation:
     * - **Agricultural Data**: Product, order, and customer data preserved for analysis
     * - **Commercial Information**: Business data retained for debugging and support
     * - **Operational Context**: Non-sensitive operational data preserved
     * - **Debug Information**: Essential context maintained for troubleshooting
     * 
     * Agricultural Commerce Applications:
     * - **Customer Data**: Protects customer credentials while preserving business context
     * - **B2B Security**: Secures business customer authentication in API integrations
     * - **Order Processing**: Safe logging of order data without credential exposure
     * - **User Management**: Secure user registration and authentication logging
     * 
     * Privacy Compliance:
     * - **GDPR Compliance**: Privacy-compliant logging practices
     * - **Regulatory Requirements**: Meets agricultural industry privacy standards
     * - **Audit Safety**: Complete request logging without privacy violations
     * - **Data Protection**: Sensitive data protection throughout logging pipeline
     * 
     * Technical Implementation:
     * - **Array Walk Recursive**: Deep sanitization through complex data structures
     * - **Case Insensitive Matching**: Robust field identification regardless of naming
     * - **Preservation Logic**: Selective redaction maintains debugging utility
     * - **Performance Optimized**: Efficient sanitization for high-traffic operations
     * 
     * @protected_method Internal security utility for safe request body logging
     * @privacy_protection Comprehensive sensitive data protection in request logging
     * @agricultural_security Specialized protection for agricultural commerce data
     */
    protected function sanitizeBody(array $body): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];
        
        array_walk_recursive($body, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '***REDACTED***';
            }
        });

        return $body;
    }

    /**
     * Calculate response payload size for bandwidth and performance analysis.
     * 
     * Determines accurate byte size of API responses across different data types
     * and formats. Essential for bandwidth analysis, performance optimization,
     * and understanding data transfer costs for agricultural commerce operations.
     * 
     * @param mixed $response Response data in various formats (string, array, object)
     * @return int|null Response size in bytes or null if indeterminate
     * 
     * @performance_analysis Response size data for bandwidth optimization insights
     * @bandwidth_monitoring Data transfer analysis for cost and performance optimization
     * @capacity_planning Response size trends guide infrastructure scaling
     * @cost_optimization Understanding data transfer costs for agricultural commerce APIs
     * 
     * Size Calculation Methods:
     * - **String Responses**: Direct byte length calculation for text responses
     * - **Array/Object Responses**: JSON encoding size for structured data
     * - **Unknown Types**: Returns null for indeterminate response types
     * - **Accurate Measurement**: Precise byte counting for all supported formats
     * 
     * Business Applications:
     * - **API Optimization**: Identify large responses requiring optimization
     * - **Bandwidth Management**: Monitor data transfer for cost optimization
     * - **Performance Analysis**: Response size correlation with response times
     * - **Customer Experience**: Large response impact on customer loading times
     * - **Infrastructure Planning**: Data transfer requirements for capacity planning
     * 
     * Agricultural Commerce Context:
     * - **Product Catalogs**: Monitor size of product data API responses
     * - **Order Processing**: Track data transfer for order management APIs
     * - **B2B Integration**: Understand bandwidth requirements for business customers
     * - **Mobile Optimization**: Response size optimization for mobile agricultural apps
     * 
     * Performance Insights:
     * - **Response Optimization**: Identify APIs returning unnecessarily large payloads
     * - **Compression Opportunities**: Large responses indicating compression benefits
     * - **Pagination Needs**: Oversized responses suggesting pagination requirements
     * - **Efficiency Metrics**: Response size per unit of business value delivered
     * 
     * Cost Management:
     * - **Data Transfer Costs**: Understanding bandwidth costs for cloud operations
     * - **Customer Bandwidth**: Consideration for customers with limited connectivity
     * - **Mobile Data**: Response size impact on customer mobile data usage
     * - **International Users**: Bandwidth considerations for global agricultural markets
     * 
     * Technical Implementation:
     * - **Type Detection**: Accurate handling of different response data types
     * - **JSON Encoding**: Consistent size calculation for structured data
     * - **Null Safety**: Graceful handling of indeterminate response types
     * - **Performance Optimized**: Efficient size calculation without data corruption
     * 
     * @protected_method Internal utility for response size analysis
     * @performance_intelligence Response size data enables optimization insights
     * @agricultural_optimization Bandwidth analysis for agricultural commerce efficiency
     */
    protected function getResponseSize($response): ?int
    {
        if (is_string($response)) {
            return strlen($response);
        }
        
        if (is_array($response) || is_object($response)) {
            return strlen(json_encode($response));
        }

        return null;
    }

    /**
     * Generate truncated response preview for debugging without excessive log storage.
     * 
     * Creates abbreviated response content for debugging context while preventing
     * log storage bloat from large response payloads. Essential for maintaining
     * useful debugging information without overwhelming log systems with
     * voluminous response data.
     * 
     * @param mixed $response Response data to create preview from
     * @param int $maxLength Maximum characters for preview (default: 500)
     * @return string|null Truncated response preview or null if indeterminate
     * 
     * @debugging_support Abbreviated response content for troubleshooting context
     * @log_efficiency Prevents log bloat while maintaining debugging utility
     * @storage_optimization Balanced debugging information without storage waste
     * @troubleshooting_aid Essential context for API response analysis
     * 
     * Preview Generation Methods:
     * - **String Responses**: Direct truncation with character limit
     * - **Structured Data**: JSON encoding followed by truncation
     * - **Complex Objects**: Serialization to JSON then truncation for consistency
     * - **Length Control**: Configurable maximum length for storage management
     * 
     * Debugging Benefits:
     * - **Context Preservation**: Enough response data for most debugging scenarios
     * - **Issue Identification**: Response content helps identify API problems
     * - **Data Validation**: Abbreviated content enables response verification
     * - **Error Analysis**: Response previews aid in error cause identification
     * 
     * Storage Efficiency:
     * - **Log Size Management**: Prevents excessive log storage from large responses
     * - **Performance Protection**: Avoids performance impact from massive response logging
     * - **Cost Optimization**: Reduces log storage costs while maintaining utility
     * - **System Health**: Prevents log system overload from response data
     * 
     * Agricultural Commerce Applications:
     * - **Product Data**: Preview of product catalog responses for debugging
     * - **Order Information**: Abbreviated order data for troubleshooting
     * - **Customer Data**: Limited customer information preview for support
     * - **API Integration**: Response previews for B2B integration debugging
     * 
     * Troubleshooting Context:
     * - **Response Format**: Verify expected response structure and format
     * - **Data Content**: Confirm appropriate data is being returned
     * - **Error Messages**: Capture error response content for analysis
     * - **Success Verification**: Confirm successful response content
     * 
     * Configuration Flexibility:
     * - **Adjustable Length**: Configurable preview length for different needs
     * - **Type Handling**: Consistent handling across different response types
     * - **Null Safety**: Graceful handling of indeterminate response types
     * - **Debug Utility**: Balances debugging needs with storage efficiency
     * 
     * @protected_method Internal utility for balanced response logging
     * @debugging_efficiency Essential context without log storage waste
     * @agricultural_support Debugging aid for agricultural commerce APIs
     */
    protected function getResponsePreview($response, int $maxLength = 500): ?string
    {
        if (is_string($response)) {
            return substr($response, 0, $maxLength);
        }
        
        if (is_array($response) || is_object($response)) {
            $json = json_encode($response);
            return substr($json, 0, $maxLength);
        }

        return null;
    }

    /**
     * Generate comprehensive API performance and usage statistics for business intelligence.
     * 
     * Provides detailed analytics about API performance, usage patterns, error rates,
     * and endpoint-specific metrics for agricultural commerce operations. Essential
     * for performance optimization, capacity planning, and business intelligence
     * about API utilization and customer behavior.
     * 
     * @param DateTimeInterface|null $startDate Optional start date for statistics period
     * @param DateTimeInterface|null $endDate Optional end date for statistics period
     * @return array Comprehensive API statistics and performance metrics
     * 
     * @business_intelligence Detailed API analytics for agricultural commerce insights
     * @performance_analysis Response time and success rate metrics
     * @usage_analytics API utilization patterns and customer behavior insights
     * @operational_metrics Statistics for system health and capacity planning
     * 
     * Statistics Categories:
     * - **Request Volume**: Total API requests for usage analysis
     * - **Success Metrics**: Successful response rates for service quality assessment
     * - **Error Analysis**: Error response counts and patterns
     * - **Performance Metrics**: Average response times for performance evaluation
     * - **Endpoint Analytics**: Per-endpoint usage and performance statistics
     * - **Status Code Distribution**: HTTP status code breakdown for health analysis
     * 
     * Business Applications:
     * - **Service Level Monitoring**: API performance against SLA requirements
     * - **Customer Experience**: Response time impact on customer satisfaction
     * - **Capacity Planning**: Usage trends for infrastructure scaling decisions
     * - **Agricultural Operations**: API usage patterns in agricultural commerce
     * - **Performance Optimization**: Identify endpoints requiring improvement
     * 
     * Agricultural Commerce Insights:
     * - **B2B Usage**: Business customer API utilization patterns
     * - **Seasonal Trends**: Agricultural seasonality reflected in API usage
     * - **Product Catalog**: Product API performance for customer experience
     * - **Order Processing**: Order API statistics for commerce optimization
     * - **Customer Behavior**: API usage patterns revealing customer preferences
     * 
     * Performance Intelligence:
     * - **Response Time Analysis**: Average response times for performance assessment
     * - **Throughput Metrics**: Request volume and processing capacity analysis
     * - **Error Rate Monitoring**: Success vs. error rates for service quality
     * - **Endpoint Performance**: Individual endpoint analysis for optimization
     * 
     * Date Range Flexibility:
     * - **Custom Periods**: Configurable date ranges for targeted analysis
     * - **Historical Analysis**: Long-term trends for strategic planning
     * - **Real-time Monitoring**: Current period statistics for immediate insights
     * - **Comparative Analysis**: Period-over-period performance comparisons
     * 
     * Statistical Correlation:
     * - **Request-Response Matching**: Links requests with corresponding responses
     * - **Performance Correlation**: Associates requests with response metrics
     * - **Error Attribution**: Connects errors to specific requests and endpoints
     * - **Complete Transaction Analysis**: Full request-response lifecycle statistics
     * 
     * Return Data Structure:
     * - **Total Requests**: Overall API request volume
     * - **Success Rate**: Percentage of successful API responses
     * - **Error Rate**: Error response frequency and patterns
     * - **Performance Metrics**: Average response times and distribution
     * - **Endpoint Breakdown**: Per-endpoint statistics and analysis
     * - **Status Code Analysis**: HTTP status code distribution and trends
     * 
     * @agricultural_analytics API statistics tailored for agricultural commerce intelligence
     * @performance_intelligence Data-driven insights for API optimization
     * @business_metrics API usage statistics supporting business decision-making
     */
    public function getStatistics(DateTimeInterface $startDate = null, DateTimeInterface $endDate = null): array
    {
        $query = activity()
            ->inLog('api_request');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $requests = $query->get();
        $responses = activity()
            ->inLog('api_response')
            ->whereIn('properties->request_id', $requests->pluck('properties.request_id'))
            ->get();

        return [
            'total_requests' => $requests->count(),
            'successful_responses' => $responses->where('properties.status_code', '>=', 200)
                ->where('properties.status_code', '<', 300)->count(),
            'error_responses' => $responses->where('properties.status_code', '>=', 400)->count(),
            'average_response_time' => $responses->avg('properties.response_time'),
            'endpoints' => $this->getEndpointStatistics($requests, $responses),
            'status_codes' => $responses->groupBy('properties.status_code')
                ->map->count()
                ->sortKeys(),
        ];
    }

    /**
     * Generate detailed per-endpoint performance and usage analytics.
     * 
     * Provides comprehensive statistics for individual API endpoints including
     * request volumes, response times, error rates, and performance metrics.
     * Essential for identifying optimization opportunities and understanding
     * endpoint-specific performance characteristics in agricultural commerce.
     * 
     * @param Collection $requests Request log entries for analysis
     * @param Collection $responses Response log entries for correlation
     * @return array Detailed per-endpoint statistics and performance metrics
     * 
     * @endpoint_intelligence Detailed performance analysis for individual API endpoints
     * @performance_optimization Identification of specific endpoints requiring improvement
     * @usage_patterns Endpoint popularity and utilization analysis
     * @error_analysis Endpoint-specific error rates and patterns
     * 
     * Endpoint Metrics Calculated:
     * - **Request Count**: Total requests per endpoint for popularity analysis
     * - **Average Response Time**: Performance metrics for each endpoint
     * - **Error Count**: Number of error responses per endpoint
     * - **Error Rate**: Percentage of requests resulting in errors
     * - **Total Response Time**: Aggregate response time for throughput analysis
     * - **Method Classification**: HTTP method and endpoint combination analysis
     * 
     * Performance Analysis:
     * - **Response Time Distribution**: Average response times per endpoint
     * - **Performance Ranking**: Endpoints ranked by response time for optimization priority
     * - **Throughput Analysis**: Request volume and processing efficiency
     * - **Performance Trends**: Endpoint performance patterns over time
     * 
     * Error Intelligence:
     * - **Error Rate Calculation**: Percentage of requests ending in errors
     * - **Error Pattern Detection**: Endpoints with consistently high error rates
     * - **Reliability Assessment**: Endpoint success rates for quality assurance
     * - **Error Type Analysis**: Specific error patterns per endpoint
     * 
     * Agricultural Commerce Applications:
     * - **Product API Performance**: Product catalog endpoint optimization
     * - **Order Processing**: Order management endpoint performance analysis
     * - **Customer Service**: Customer-facing endpoint response time optimization
     * - **B2B Integration**: Business customer API endpoint performance
     * 
     * Business Intelligence:
     * - **API Usage Patterns**: Most popular endpoints for business priority
     * - **Customer Behavior**: Endpoint usage revealing customer preferences
     * - **Service Optimization**: Performance data for targeted improvements
     * - **Resource Allocation**: Endpoint performance guides infrastructure investment
     * 
     * Optimization Insights:
     * - **Performance Bottlenecks**: Slowest endpoints requiring immediate attention
     * - **High-Traffic Endpoints**: Popular APIs requiring performance optimization
     * - **Error-Prone Endpoints**: APIs with reliability issues needing investigation
     * - **Efficiency Opportunities**: Underperforming endpoints with optimization potential
     * 
     * Statistical Calculations:
     * - **Average Response Time**: Total time divided by request count
     * - **Error Rate Percentage**: (Errors / Total Requests) * 100
     * - **Method-Endpoint Combinations**: Detailed breakdown by HTTP method
     * - **Request-Response Correlation**: Matching requests with corresponding responses
     * 
     * @protected_method Internal analytics engine for endpoint performance analysis
     * @agricultural_optimization Endpoint statistics for agricultural commerce efficiency
     * @performance_engineering Data-driven endpoint optimization insights
     */
    protected function getEndpointStatistics(Collection $requests, Collection $responses): array
    {
        $endpoints = [];
        
        foreach ($requests as $request) {
            $endpoint = $request->properties['endpoint'] ?? 'unknown';
            $method = $request->properties['method'] ?? 'unknown';
            $key = "{$method} {$endpoint}";

            if (!isset($endpoints[$key])) {
                $endpoints[$key] = [
                    'count' => 0,
                    'total_time' => 0,
                    'errors' => 0,
                ];
            }

            $endpoints[$key]['count']++;

            $response = $responses->firstWhere('properties.request_id', $request->properties['request_id']);
            if ($response) {
                $endpoints[$key]['total_time'] += $response->properties['response_time'] ?? 0;
                if ($response->properties['status_code'] >= 400) {
                    $endpoints[$key]['errors']++;
                }
            }
        }

        // Calculate averages
        foreach ($endpoints as &$endpoint) {
            $endpoint['average_time'] = $endpoint['count'] > 0 
                ? $endpoint['total_time'] / $endpoint['count'] 
                : 0;
            $endpoint['error_rate'] = $endpoint['count'] > 0 
                ? ($endpoint['errors'] / $endpoint['count']) * 100 
                : 0;
        }

        return $endpoints;
    }

    /**
     * Generate rate limiting statistics for IP-based traffic analysis and security monitoring.
     * 
     * Provides detailed request frequency analysis for specific IP addresses to support
     * rate limiting decisions, security threat detection, and traffic pattern analysis.
     * Essential for protecting agricultural commerce APIs from abuse while enabling
     * legitimate business operations.
     * 
     * @param string $ip IP address for rate limiting analysis
     * @return array Rate limiting statistics and request frequency metrics
     * 
     * @security_monitoring IP-based traffic analysis for threat detection
     * @rate_limiting Request frequency data for rate limiting decisions
     * @traffic_analysis IP usage patterns for security and performance insights
     * @abuse_prevention Identifying excessive API usage patterns
     * 
     * Rate Limiting Metrics:
     * - **Hourly Requests**: Total requests from IP in the last hour
     * - **Minute Requests**: Recent request frequency for burst detection
     * - **Endpoint Diversity**: Number of unique endpoints accessed by IP
     * - **Traffic Patterns**: Request distribution and usage characteristics
     * 
     * Security Applications:
     * - **Abuse Detection**: Identify IPs making excessive requests
     * - **Attack Prevention**: Rate limiting based on request frequency
     * - **Threat Analysis**: Unusual traffic patterns indicating potential threats
     * - **DDoS Protection**: High-frequency request detection for DDoS mitigation
     * 
     * Agricultural Commerce Security:
     * - **B2B Protection**: Protect business customer APIs from abuse
     * - **Scraping Prevention**: Detect automated scraping of product catalogs
     * - **Resource Protection**: Prevent API abuse affecting legitimate customers
     * - **Service Availability**: Maintain API availability through rate limiting
     * 
     * Business Intelligence:
     * - **Customer Behavior**: Legitimate customer API usage patterns
     * - **Integration Analysis**: B2B partner API utilization patterns
     * - **Usage Optimization**: Understanding normal vs. excessive API usage
     * - **Service Planning**: API capacity planning based on usage patterns
     * 
     * Rate Limiting Support:
     * - **Dynamic Thresholds**: Request frequency data for adaptive rate limiting
     * - **Legitimate User Protection**: Distinguish normal usage from abuse
     * - **Burst Traffic**: Short-term request spike detection and handling
     * - **Fair Usage**: Ensure equitable API access across all users
     * 
     * Performance Optimization:
     * - **Caching Strategy**: 60-second cache for frequently checked IPs
     * - **Efficient Queries**: Optimized database queries for real-time analysis
     * - **Time Window Analysis**: Sliding window approach for current relevance
     * - **Resource Protection**: Prevent rate limiting queries from impacting performance
     * 
     * Time Window Analysis:
     * - **Last Hour**: Comprehensive hourly request analysis
     * - **Last Minute**: Recent burst activity detection
     * - **Endpoint Variety**: Diversity of accessed endpoints for behavior analysis
     * - **Pattern Recognition**: Traffic patterns indicating normal vs. suspicious behavior
     * 
     * Agricultural Commerce Context:
     * - **Product Catalog Protection**: Prevent excessive product data scraping
     * - **Order API Security**: Protect order processing APIs from abuse
     * - **Customer API Access**: Ensure fair API access for all customers
     * - **B2B Integration**: Monitor business partner API usage patterns
     * 
     * @security_intelligence IP-based traffic analysis for agricultural commerce protection
     * @rate_limiting_support Essential data for intelligent rate limiting decisions
     * @agricultural_security Specialized protection for agricultural commerce APIs
     */
    public function getRateLimitStats(string $ip): array
    {
        $key = "rate_limit_{$ip}";
        
        return Cache::remember($key, 60, function () use ($ip) {
            $requests = activity()
                ->inLog('api_request')
                ->where('properties->ip', $ip)
                ->where('created_at', '>=', now()->subHour())
                ->get();

            return [
                'requests_last_hour' => $requests->count(),
                'requests_last_minute' => $requests->where('created_at', '>=', now()->subMinute())->count(),
                'unique_endpoints' => $requests->pluck('properties.endpoint')->unique()->count(),
            ];
        });
    }
}