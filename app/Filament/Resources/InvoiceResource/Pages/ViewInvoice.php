<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;
    protected static string $view = 'filament.resources.invoice-resource.pages.view-invoice';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('invoices.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['sent', 'paid', 'overdue', 'pending'])),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make()
                    ->schema([
                        Components\View::make('components.invoice-preview')
                            ->viewData(['invoice' => $this->record]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}