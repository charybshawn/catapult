<?php

namespace App\Filament\Resources\CropResource\Actions;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

/**
 * Crop Batch Debug Action
 * Simplified action that delegates all display logic to the view
 */
class CropBatchDebugAction
{
    /**
     * Create the debug action
     */
    public static function make(): Action
    {
        return Action::make('debug')
            ->label('')
            ->icon('heroicon-o-code-bracket')
            ->tooltip('Debug Info')
            ->action(function ($record) {
                // Get stage history for the batch
                $stageHistory = \App\Models\CropStageHistory::where('crop_batch_id', $record->id)
                    ->with(['stage', 'createdBy'])
                    ->orderBy('entered_at', 'asc')
                    ->get();
                
                $htmlOutput = view('filament.actions.crop-batch-debug', [
                    'record' => $record,
                    'firstCrop' => \App\Models\Crop::where('crop_batch_id', $record->id)->first(),
                    'recipe' => \App\Models\Recipe::find($record->recipe_id),
                    'stageHistory' => $stageHistory,
                ])->render();

                Notification::make()
                    ->title('Crop Batch Debug Information')
                    ->body($htmlOutput)
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ])
                    ->send();
            });
    }
}