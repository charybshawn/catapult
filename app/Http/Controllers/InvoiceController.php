<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Download an invoice as PDF.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
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
     * Download a regular (single order) invoice as PDF.
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
     * Download a consolidated invoice as PDF.
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
