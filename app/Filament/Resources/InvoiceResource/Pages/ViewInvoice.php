<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;

/**
 * Filament page for viewing agricultural invoice records with comprehensive display.
 *
 * Provides read-only invoice viewing interface with PDF generation capabilities
 * and comprehensive invoice preview. Supports both single and consolidated invoice
 * display for agricultural business financial operations and customer communications.
 *
 * @filament_page
 * @business_domain Agricultural invoice viewing and PDF generation
 * @related_models Invoice, Order, OrderItem, Product
 * @workflow_support Invoice preview, PDF download, customer communications
 * @financial_display Comprehensive invoice presentation for business operations
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;
    protected string $view = 'filament.resources.invoice-resource.pages.view-invoice';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('invoices.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['sent', 'paid', 'overdue', 'pending'])),
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        View::make('components.invoice-preview')
                            ->viewData(['invoice' => $this->record]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}