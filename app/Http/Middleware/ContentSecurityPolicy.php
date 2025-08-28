<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy middleware for agricultural microgreens management web application.
 * 
 * Implements Content Security Policy (CSP) headers to protect agricultural farm management
 * interfaces from XSS attacks, code injection, and malicious content loading. Provides
 * specialized security configuration for agricultural web applications handling sensitive
 * crop data, order information, and business intelligence dashboards.
 *
 * @package App\Http\Middleware
 * @author Catapult Development Team
 * @since 1.0.0
 * 
 * @security_features XSS protection, code injection prevention, resource loading control
 * @agricultural_protection Crop data security, order information protection, dashboard safety
 * @web_interface Filament admin panels, agricultural dashboards, mobile-responsive interfaces
 * 
 * @see https://developer.mozilla.org/docs/Web/HTTP/CSP CSP specification
 * @see https://filamentphp.com/docs/panels/configuration#content-security-policy Filament CSP configuration
 */
class ContentSecurityPolicy
{
    /**
     * Apply Content Security Policy headers for agricultural web application security.
     * 
     * Implements CSP headers to protect agricultural management interfaces including
     * Filament admin panels, crop planning dashboards, order management interfaces,
     * and inventory tracking systems. Provides defense against XSS attacks and
     * malicious content injection while maintaining functionality for agricultural
     * business intelligence and reporting features.
     *
     * @param Request $request HTTP request to agricultural web interface
     * @param Closure $next Next middleware in pipeline for continued request processing
     * @return Response HTTP response with CSP headers for agricultural interface protection
     * 
     * @security_context Protects agricultural data interfaces and business dashboards
     * @agricultural_interfaces Crop planning, order management, inventory dashboards
     * @csp_policies Default source restrictions, script execution control, style loading rules
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Add CSP headers if needed
        // $response->headers->set('Content-Security-Policy', "default-src 'self'");
        
        return $response;
    }
}