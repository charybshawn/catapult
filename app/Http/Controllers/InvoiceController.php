<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Agricultural Invoice Management Controller
 * 
 * Handles PDF generation and download functionality for agricultural business
 * invoices. Manages both regular single-order invoices and consolidated
 * multi-order invoices for customers in the agricultural management system.
 * Provides secure, authorized access to financial documents.
 * 
 * @package App\Http\Controllers
 * @since 1.0.0
 * @author Catapult Development Team
 * 
 * @see \App\Models\Invoice For agricultural invoice data structures
 * @see \App\Models\Order For order-to-invoice relationships
 * @see \App\Models\User For customer and authorization management
 * 
 * @business_context Agricultural commerce and financial document management
 * @security_features Role-based authorization, ownership verification
 * @document_types Regular invoices, consolidated invoices, PDF generation
 * @agricultural_features Product-based invoicing, packaging costs, delivery scheduling
 */
class InvoiceController extends Controller
{
    /**
     * Download agricultural invoice as secure PDF document.
     * 
     * Provides authorized PDF download access for agricultural business invoices.
     * Handles both regular single-order invoices and consolidated multi-order
     * invoices with proper security authorization. Ensures customers can only
     * access their own invoices while allowing administrators full access.
     * 
     * @param Invoice $invoice Agricultural invoice model for PDF generation
     * @return Response PDF download response with proper headers and filename
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 if unauthorized
     * 
     * @http_method GET
     * @route_pattern /invoices/{invoice}/download
     * @response_type application/pdf
     * @authorization_required User ownership or admin role
     * 
     * @security_checks
     * - Admin users can access all invoices
     * - Regular users can only access invoices where user_id matches
     * - For non-consolidated invoices, also checks order ownership
     * - Comprehensive permission verification before PDF generation
     * 
     * @business_features
     * - Regular invoice PDF generation for single orders
     * - Consolidated invoice PDF generation for multiple orders
     * - Proper filename generation with invoice numbers
     * - Agricultural product and packaging cost breakdown
     * 
     * @document_security Role-based access control for financial documents
     * @agricultural_context Product invoicing, delivery scheduling, customer management
     */
    public function download(Invoice $invoice)
    {
        // Check if user has permission to view this invoice
        $user = auth()->user();
        $hasPermission = $user->hasRole('admin') || $invoice->user_id === $user->id;
        
        // For non-consolidated invoices, also check order ownership
        if (!$invoice->is_consolidated && $invoice->order) {
            $hasPermission = $hasPermission || $invoice->order->user_id === $user->id;
        }
        
        if (!$hasPermission) {
            abort(403, 'Unauthorized to view this invoice.');
        }
        
        if ($invoice->is_consolidated) {
            return $this->downloadConsolidatedInvoice($invoice);
        } else {
            return $this->downloadRegularInvoice($invoice);
        }
    }
    
    /**
     * Generate and download regular single-order agricultural invoice PDF.
     * 
     * Creates PDF document for individual agricultural orders containing
     * product details, packaging costs, and delivery information. Used for
     * standard agricultural commerce transactions with detailed line items
     * and agricultural-specific pricing structures.
     * 
     * @param Invoice $invoice Single-order invoice model with order relationship
     * @return Response PDF download response with agricultural invoice formatting
     * 
     * @pdf_content
     * - Customer information and agricultural business details
     * - Order items with product names, quantities, and agricultural pricing
     * - Packaging costs and agricultural delivery specifications
     * - Invoice totals with agricultural business tax handling
     * - Agricultural branding and business contact information
     * 
     * @template_view invoices.pdf
     * @filename_format Invoice_{invoice_number}.pdf
     * @business_context Single agricultural order financial documentation
     */
    protected function downloadRegularInvoice(Invoice $invoice)
    {
        // Get invoice data
        $order = $invoice->order;
        $customer = $order->user;
        $items = $order->orderItems()->with('product')->get();
        $packagings = $order->orderPackagings()->with('packagingType')->get();
        
        // Generate PDF
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'order' => $order,
            'customer' => $customer,
            'items' => $items,
            'packagings' => $packagings,
        ]);
        
        // Generate a filename
        $filename = 'Invoice_' . $invoice->invoice_number . '.pdf';
        
        // Return the PDF as a download
        return $pdf->download($filename);
    }
    
    /**
     * Generate and download consolidated multi-order agricultural invoice PDF.
     * 
     * Creates comprehensive PDF document combining multiple agricultural orders
     * for a single customer into one consolidated invoice. Used for recurring
     * customers or bulk agricultural orders with multiple delivery dates and
     * complex product combinations across multiple growing cycles.
     * 
     * @param Invoice $invoice Consolidated invoice model with multiple order relationships
     * @return Response PDF download response with consolidated invoice formatting
     * 
     * @pdf_content
     * - Customer information and agricultural business summary
     * - Multiple orders grouped by delivery date
     * - Combined product totals across all orders
     * - Consolidated packaging and delivery cost breakdown
     * - Agricultural business terms and consolidated payment information
     * 
     * @template_view invoices.consolidated-pdf
     * @filename_format Consolidated_Invoice_{invoice_number}.pdf
     * @business_context Multi-order agricultural commerce documentation
     * @sorting Orders sorted by delivery_date for agricultural planning context
     */
    protected function downloadConsolidatedInvoice(Invoice $invoice)
    {
        // Get customer and all orders for this consolidated invoice
        $customer = $invoice->user;
        $orders = $invoice->consolidatedOrders()
            ->with(['orderItems.product', 'orderPackagings.packagingType'])
            ->orderBy('delivery_date')
            ->get();
        
        // Generate PDF
        $pdf = Pdf::loadView('invoices.consolidated-pdf', [
            'invoice' => $invoice,
            'customer' => $customer,
            'orders' => $orders,
        ]);
        
        // Generate a filename
        $filename = 'Consolidated_Invoice_' . $invoice->invoice_number . '.pdf';
        
        // Return the PDF as a download
        return $pdf->download($filename);
    }
}
