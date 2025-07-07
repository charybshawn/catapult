<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\View;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions for viewing activity logs
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('log_name')
                            ->label('Log Name')
                            ->badge(),
                        TextEntry::make('description')
                            ->label('Description'),
                        TextEntry::make('subject_type')
                            ->label('Subject Type')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('subject_id')
                            ->label('Subject ID'),
                        TextEntry::make('event')
                            ->label('Event')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('causer_type')
                            ->label('Causer Type'),
                        TextEntry::make('causer_id')
                            ->label('Causer ID'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ])
                    ->columns(2),
                    
                Section::make('Properties')
                    ->schema([
                        View::make('filament.resources.activity-resource.components.properties-display')
                            ->viewData([
                                'properties' => $this->record->properties,
                            ]),
                    ])
                    ->visible(fn () => !empty($this->record->properties) && !isset($this->record->properties['relationships'])),
                    
                Section::make('Properties & Relationships')
                    ->schema([
                        View::make('filament.resources.activity-resource.components.properties-display')
                            ->viewData([
                                'properties' => array_diff_key($this->record->properties ?? [], ['relationships' => true]),
                            ])
                            ->visible(fn () => !empty(array_diff_key($this->record->properties ?? [], ['relationships' => true]))),
                        View::make('filament.resources.activity-resource.components.relationships-display')
                            ->viewData([
                                'properties' => $this->record->properties,
                            ]),
                    ])
                    ->visible(fn () => !empty($this->record->properties) && isset($this->record->properties['relationships'])),
                    
                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('batch_uuid')
                            ->label('Batch UUID')
                            ->visible(fn ($record) => !empty($record->batch_uuid)),
                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->visible(fn ($record) => !empty($record->ip_address)),
                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->visible(fn ($record) => !empty($record->user_agent)),
                        TextEntry::make('request_method')
                            ->label('Request Method')
                            ->badge()
                            ->visible(fn ($record) => !empty($record->request_method)),
                        TextEntry::make('request_url')
                            ->label('Request URL')
                            ->visible(fn ($record) => !empty($record->request_url))
                            ->columnSpanFull(),
                        TextEntry::make('response_status')
                            ->label('Response Status')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 200 && $state < 300 => 'success',
                                $state >= 300 && $state < 400 => 'info',
                                $state >= 400 && $state < 500 => 'warning',
                                $state >= 500 => 'danger',
                                default => 'gray',
                            })
                            ->visible(fn ($record) => !empty($record->response_status)),
                        TextEntry::make('execution_time_ms')
                            ->label('Execution Time')
                            ->suffix(' ms')
                            ->visible(fn ($record) => !empty($record->execution_time_ms)),
                        TextEntry::make('memory_usage_mb')
                            ->label('Memory Usage')
                            ->suffix(' MB')
                            ->visible(fn ($record) => !empty($record->memory_usage_mb)),
                        TextEntry::make('query_count')
                            ->label('Query Count')
                            ->visible(fn ($record) => !empty($record->query_count)),
                        TextEntry::make('severity_level')
                            ->label('Severity Level')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'debug' => 'gray',
                                'info' => 'info',
                                'notice' => 'primary',
                                'warning' => 'warning',
                                'error' => 'danger',
                                'critical' => 'danger',
                                'alert', 'emergency' => 'danger',
                                default => 'gray',
                            })
                            ->visible(fn ($record) => !empty($record->severity_level)),
                    ])
                    ->columns(3)
                    ->visible(fn () => 
                        !empty($this->record->ip_address) || 
                        !empty($this->record->user_agent) || 
                        !empty($this->record->request_method) ||
                        !empty($this->record->execution_time_ms)
                    ),
                    
                Section::make('Context & Tags')
                    ->schema([
                        View::make('filament.resources.activity-resource.components.context-display')
                            ->viewData([
                                'context' => $this->record->context,
                            ])
                            ->visible(fn () => !empty($this->record->context)),
                        View::make('filament.resources.activity-resource.components.tags-display')
                            ->viewData([
                                'tags' => $this->record->tags,
                            ])
                            ->visible(fn () => !empty($this->record->tags)),
                    ])
                    ->visible(fn () => !empty($this->record->context) || !empty($this->record->tags)),
            ]);
    }
} 