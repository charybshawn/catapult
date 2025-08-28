<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Filament page for listing and managing agricultural invoice records.
 *
 * Provides comprehensive invoice listing and management interface for agricultural
 * business operations. Supports creation of new invoices and integration with
 * microgreens sales workflows for financial tracking and customer billing management.
 *
 * @filament_page
 * @business_domain Agricultural invoice listing and financial management
 * @related_models Invoice, Order, Customer
 * @workflow_support Invoice listing, creation, financial reporting
 * @financial_operations Invoice management for microgreens sales operations
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
