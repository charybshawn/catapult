<?php

namespace App\Filament\Resources\CropResource\Tables;

use App\Models\Crop;
use App\Models\CropStage;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;

class CropTable
{
    public static function modifyQuery(Builder $query): Builder
    {
        // Eager load relationships for optimal performance
        return $query->with([
            'recipe',
            'recipe.masterCultivar',
            'recipe.masterSeedCatalog',
            'currentStage'
        ]);
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('Crop ID')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('crop_batch_id')
                ->label('Batch ID')
                ->sortable()
                ->searchable(),
            
            Tables\Columns\TextColumn::make('recipe.name')
                ->label('Recipe')
                ->sortable()
                ->searchable()
                ->limit(30),
            
            Tables\Columns\TextColumn::make('currentStage.name')
                ->label('Stage')
                ->badge()
                ->colors(static::getStageColors()),
            
            Tables\Columns\TextColumn::make('tray_number')
                ->label('Tray')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('tray_count')
                ->label('Count')
                ->numeric()
                ->sortable(),
            
            // Note: time_to_next_stage_display and stage_age_display are now in crop_batches_list_view
            // For individual crop view, we could calculate these on the fly or access via relationship
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Started')
                ->date('M j, Y')
                ->sortable(),
            
            // Note: expected_harvest_at is now in crop_batches_list_view
        ];
    }
    
    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('current_stage_id')
                ->label('Stage')
                ->options(CropStage::pluck('name', 'id'))
                ->multiple(),
            
            Tables\Filters\SelectFilter::make('recipe_id')
                ->label('Recipe')
                ->relationship('recipe', 'name')
                ->searchable()
                ->preload(),
            
            Tables\Filters\Filter::make('has_order')
                ->label('Has Order')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('order_id')),
            
            Tables\Filters\Filter::make('soaking')
                ->label('Currently Soaking')
                ->query(fn (Builder $query): Builder => $query->whereHas('currentStage', fn ($q) => $q->where('code', 'soaking'))),
        ];
    }
    
    public static function actions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('advanceFromSoaking')
                    ->label('Advance to Germination')
                    ->icon('heroicon-o-arrow-right')
                    ->color('success')
                    ->visible(fn (Crop $record) => $record->isInSoaking())
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Select::make('tray_prefix')
                            ->label('Tray Section')
                            ->options([
                                'A' => 'Section A',
                                'B' => 'Section B', 
                                'C' => 'Section C',
                                'D' => 'Section D',
                                'E' => 'Section E',
                                'F' => 'Section F',
                                'G' => 'Section G',
                                'H' => 'Section H',
                            ])
                            ->required()
                            ->searchable()
                            ->helperText('Crops will be assigned sequential tray numbers in this section'),
                    ])
                    ->action(function (Crop $record, array $data) {
                        $advanceAction = app(\App\Actions\Crop\AdvanceFromSoaking::class);
                        $succeeded = 0;
                        $failed = 0;
                        
                        try {
                            $trayNumber = $data['tray_prefix'] . '1';
                            $advanceAction->execute($record, $trayNumber);
                            $succeeded++;
                        } catch (\Exception $e) {
                            $failed++;
                            Log::error('Failed to advance crop from soaking', [
                                'crop_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        if ($succeeded > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Crop Advanced')
                                ->body("Successfully advanced crop to germination stage")
                                ->success()
                                ->send();
                        }
                        
                        if ($failed > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Crop Failed to Advance')
                                ->body("Crop could not be advanced. Check logs for details.")
                                ->warning()
                                ->send();
                        }
                    }),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]),
        ];
    }
    
    public static function groups(): array
    {
        return [
            Tables\Grouping\Group::make('recipe.name')
                ->label('Recipe')
                ->collapsible(),
            
            Tables\Grouping\Group::make('currentStage.name')
                ->label('Stage')
                ->collapsible(),
                
            Tables\Grouping\Group::make('created_at')
                ->label('Started Date')
                ->date()
                ->collapsible(),
        ];
    }
    
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('bulkAdvanceFromSoaking')
                ->label('Advance From Soaking')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->modalHeading('Advance Crops from Soaking')
                ->modalDescription('This will advance all selected soaking crops to germination stage.')
                ->form([
                    \Filament\Forms\Components\Select::make('tray_prefix')
                        ->label('Tray Prefix')
                        ->options([
                            'A' => 'Section A',
                            'B' => 'Section B', 
                            'C' => 'Section C',
                            'D' => 'Section D',
                            'E' => 'Section E',
                            'F' => 'Section F',
                            'G' => 'Section G',
                            'H' => 'Section H',
                        ])
                        ->required()
                        ->searchable()
                        ->helperText('Crops will be assigned trays like A1, A2, etc.'),
                ])
                ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                    $advanceAction = app(\App\Actions\Crop\AdvanceFromSoaking::class);
                    $succeeded = 0;
                    $failed = 0;
                    $trayNumber = 1;
                    
                    foreach ($records as $crop) {
                        // Skip non-soaking crops
                        if (!$crop->isInSoaking()) {
                            continue;
                        }
                        
                        try {
                            $trayCode = $data['tray_prefix'] . $trayNumber;
                            $advanceAction->execute($crop, $trayCode);
                            $succeeded++;
                            $trayNumber++;
                        } catch (\Exception $e) {
                            $failed++;
                            \Illuminate\Support\Facades\Log::error('Failed to advance crop from soaking', [
                                'crop_id' => $crop->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    if ($succeeded > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Crops Advanced')
                            ->body("Successfully advanced {$succeeded} crops to germination stage.")
                            ->success()
                            ->send();
                    }
                    
                    if ($failed > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Some Crops Failed')
                            ->body("{$failed} crops could not be advanced. Check logs for details.")
                            ->warning()
                            ->send();
                    }
                }),
            
            Tables\Actions\DeleteBulkAction::make(),
        ];
    }
    
    protected static function getStageColors(): array
    {
        return [
            'default' => 'gray',
            'Soaking' => Color::Blue,
            'Germination' => Color::Yellow,
            'Blackout' => Color::Gray,
            'Light' => Color::Green,
            'Harvested' => Color::Purple,
        ];
    }
    
    protected static function getTimeToNextStageDisplay(Crop $record): string
    {
        $currentStage = $record->getRelation('currentStage');
        if (!$currentStage) {
            return 'Unknown';
        }
        
        if ($currentStage->code === 'harvested') {
            return 'Complete';
        }
        
        if ($record->getRelation('currentStage')?->code === 'soaking' && $record->isActivelySoaking()) {
            $remaining = $record->getSoakingTimeRemaining();
            if ($remaining !== null) {
                $hours = intval($remaining / 60);
                $minutes = $remaining % 60;
                return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
            }
        }
        
        return $record->time_to_next_stage_display ?? 'Unknown';
    }
}