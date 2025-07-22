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
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
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
            
            Tables\Columns\TextColumn::make('time_to_next_stage_display')
                ->label('Time to Next Stage')
                ->getStateUsing(fn (Crop $record) => static::getTimeToNextStageDisplay($record)),
            
            Tables\Columns\TextColumn::make('stage_age_display')
                ->label('Stage Age'),
            
            Tables\Columns\TextColumn::make('order.id')
                ->label('Order')
                ->sortable()
                ->url(fn ($record) => $record->order_id ? route('filament.admin.resources.orders.edit', $record->order_id) : null),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Started')
                ->dateTime('M j, Y')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime('M j, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
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
            Tables\Actions\Action::make('advanceFromSoaking')
                ->label('Advance to Germination')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->visible(fn (Crop $record) => $record->getRelation('currentStage')?->code === 'soaking')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\TextInput::make('tray_number')
                        ->label('Assign Tray Number')
                        ->required()
                        ->placeholder('e.g., A1, B2, etc.')
                        ->helperText('Assign a tray number for this crop'),
                ])
                ->action(function (Crop $record, array $data) {
                    $advanceAction = app(\App\Actions\Crop\AdvanceFromSoaking::class);
                    
                    try {
                        $advanceAction->execute($record, $data['tray_number']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Crop Advanced')
                            ->body("Crop advanced to germination stage with tray {$data['tray_number']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error Advancing Crop')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
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
                    \Filament\Forms\Components\TextInput::make('tray_prefix')
                        ->label('Tray Prefix')
                        ->required()
                        ->placeholder('e.g., A, B, C')
                        ->helperText('Crops will be assigned trays like A1, A2, etc.'),
                ])
                ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                    $advanceAction = app(\App\Actions\Crop\AdvanceFromSoaking::class);
                    $succeeded = 0;
                    $failed = 0;
                    $trayNumber = 1;
                    
                    foreach ($records as $record) {
                        // Skip non-soaking crops
                        if ($record->getRelation('currentStage')?->code !== 'soaking') {
                            continue;
                        }
                        
                        try {
                            $trayCode = $data['tray_prefix'] . $trayNumber;
                            $advanceAction->execute($record, $trayCode);
                            $succeeded++;
                            $trayNumber++;
                        } catch (\Exception $e) {
                            $failed++;
                            \Illuminate\Support\Facades\Log::error('Failed to advance crop from soaking', [
                                'crop_id' => $record->id,
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