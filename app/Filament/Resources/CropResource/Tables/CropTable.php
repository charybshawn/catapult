<?php

namespace App\Filament\Resources\CropResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Actions\Crop\AdvanceFromSoaking;
use Exception;
use Filament\Notifications\Notification;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Grouping\Group;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Actions\DeleteBulkAction;
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
            TextColumn::make('id')
                ->label('Crop ID')
                ->sortable(),
            
            TextColumn::make('crop_batch_id')
                ->label('Batch ID')
                ->sortable()
                ->searchable(),
            
            TextColumn::make('recipe.name')
                ->label('Recipe')
                ->sortable()
                ->searchable()
                ->limit(30),
            
            TextColumn::make('currentStage.name')
                ->label('Stage')
                ->badge()
                ->colors(static::getStageColors()),
            
            TextColumn::make('tray_number')
                ->label('Tray')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('tray_count')
                ->label('Count')
                ->numeric()
                ->sortable(),
            
            // Note: time_to_next_stage_display and stage_age_display are now in crop_batches_list_view
            // For individual crop view, we could calculate these on the fly or access via relationship
            
            TextColumn::make('created_at')
                ->label('Started')
                ->date('M j, Y')
                ->sortable(),
            
            // Note: expected_harvest_at is now in crop_batches_list_view
        ];
    }
    
    public static function filters(): array
    {
        return [
            SelectFilter::make('current_stage_id')
                ->label('Stage')
                ->options(CropStage::pluck('name', 'id'))
                ->multiple(),
            
            SelectFilter::make('recipe_id')
                ->label('Recipe')
                ->relationship('recipe', 'name')
                ->searchable()
                ->preload(),
            
            Filter::make('has_order')
                ->label('Has Order')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('order_id')),
            
            Filter::make('soaking')
                ->label('Currently Soaking')
                ->query(fn (Builder $query): Builder => $query->whereHas('currentStage', fn ($q) => $q->where('code', 'soaking'))),
        ];
    }
    
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                Action::make('advanceFromSoaking')
                    ->label('Advance to Germination')
                    ->icon('heroicon-o-arrow-right')
                    ->color('success')
                    ->visible(fn (Crop $record) => $record->isInSoaking())
                    ->requiresConfirmation()
                    ->schema([
                        Select::make('tray_prefix')
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
                        $advanceAction = app(AdvanceFromSoaking::class);
                        $succeeded = 0;
                        $failed = 0;
                        
                        try {
                            $trayNumber = $data['tray_prefix'] . '1';
                            $advanceAction->execute($record, $trayNumber);
                            $succeeded++;
                        } catch (Exception $e) {
                            $failed++;
                            Log::error('Failed to advance crop from soaking', [
                                'crop_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        if ($succeeded > 0) {
                            Notification::make()
                                ->title('Crop Advanced')
                                ->body("Successfully advanced crop to germination stage")
                                ->success()
                                ->send();
                        }
                        
                        if ($failed > 0) {
                            Notification::make()
                                ->title('Crop Failed to Advance')
                                ->body("Crop could not be advanced. Check logs for details.")
                                ->warning()
                                ->send();
                        }
                    }),
                
                ViewAction::make(),
                EditAction::make(),
            ]),
        ];
    }
    
    public static function groups(): array
    {
        return [
            Group::make('recipe.name')
                ->label('Recipe')
                ->collapsible(),
            
            Group::make('currentStage.name')
                ->label('Stage')
                ->collapsible(),
                
            Group::make('created_at')
                ->label('Started Date')
                ->date()
                ->collapsible(),
        ];
    }
    
    public static function bulkActions(): array
    {
        return [
            BulkAction::make('bulkAdvanceFromSoaking')
                ->label('Advance From Soaking')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->modalHeading('Advance Crops from Soaking')
                ->modalDescription('This will advance all selected soaking crops to germination stage.')
                ->form([
                    Select::make('tray_prefix')
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
                ->action(function (Collection $records, array $data) {
                    $advanceAction = app(AdvanceFromSoaking::class);
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
                        } catch (Exception $e) {
                            $failed++;
                            Log::error('Failed to advance crop from soaking', [
                                'crop_id' => $crop->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    if ($succeeded > 0) {
                        Notification::make()
                            ->title('Crops Advanced')
                            ->body("Successfully advanced {$succeeded} crops to germination stage.")
                            ->success()
                            ->send();
                    }
                    
                    if ($failed > 0) {
                        Notification::make()
                            ->title('Some Crops Failed')
                            ->body("{$failed} crops could not be advanced. Check logs for details.")
                            ->warning()
                            ->send();
                    }
                }),
            
            DeleteBulkAction::make(),
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