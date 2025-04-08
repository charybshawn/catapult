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
        // Ensure the invoice exists and the user has permission to view it
        if (!$invoice || (auth()->user()->role !== 'admin' && auth()->user()->id !== $invoice->order->user_id)) {
            abort(404);
        }
        
        // Get invoice data
        $order = $invoice->order;
        $customer = $order->user;
        $items = $order->orderItems()->with('item')->get();
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
}
