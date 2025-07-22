<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Actions\Order\ApproveAllPlansAction;
use App\Actions\Order\BulkApprovePlansAction;
use App\Actions\Order\GenerateOrderPlansAction;
use App\Actions\Order\ValidateOrderPlanAction;
use App\Filament\Resources\OrderResource\Forms\CropPlansForm;
use App\Filament\Resources\OrderResource\Tables\CropPlansTable;
use App\Models\CropPlan;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CropPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'cropPlans';
    protected static ?string $title = 'Crop Plans';
    protected static ?string $navigationLabel = 'Crop Plans';
    protected static ?string $icon = 'heroicon-o-calendar';

    public function form(Form $form): Form
    {
        return $form->schema(CropPlansForm::schema());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns(CropPlansTable::columns())
            ->defaultSort('plant_by_date', 'asc')
            ->filters(CropPlansTable::filters())
            ->headerActions($this->getTableHeaderActions())
            ->actions($this->getTableActions())
            ->bulkActions($this->getTableBulkActions());
    }

    public function isReadOnly(): bool
    {
        return app(ValidateOrderPlanAction::class)->isReadOnly($this->getOwnerRecord());
    }

    /**
     * Get table header actions with business logic delegation
     */
    private function getTableHeaderActions(): array
    {
        $baseActions = CropPlansTable::headerActions($this->getOwnerRecord());
        
        return array_map(function ($action) {
            if ($action->getName() === 'generate_plans') {
                return $action->action(function () {
                    $result = app(GenerateOrderPlansAction::class)->execute($this->getOwnerRecord());
                    $this->sendNotification($result);
                });
            }
            
            if ($action->getName() === 'approve_all') {
                return $action->action(function () {
                    $result = app(ApproveAllPlansAction::class)->execute($this->getOwnerRecord(), auth()->user());
                    $this->sendNotification($result);
                });
            }
            
            return $action;
        }, $baseActions);
    }

    /**
     * Get table row actions with business logic delegation
     */
    private function getTableActions(): array
    {
        $baseActions = CropPlansTable::actions();
        
        return array_map(function ($action) {
            if ($action->getName() === 'approve') {
                return $action->action(function (CropPlan $record) {
                    $result = app(ValidateOrderPlanAction::class)->approvePlan($record, auth()->user());
                    $this->sendNotification($result);
                });
            }
            
            if ($action->getName() === 'cancel') {
                return $action->action(function (CropPlan $record) {
                    $result = app(ValidateOrderPlanAction::class)->cancelPlan($record);
                    $this->sendNotification($result);
                });
            }
            
            return $action;
        }, $baseActions);
    }

    /**
     * Get table bulk actions with business logic delegation
     */
    private function getTableBulkActions(): array
    {
        $baseActions = CropPlansTable::bulkActions();
        
        return array_map(function ($action) {
            if ($action->getName() === 'approve_selected') {
                return $action->action(function ($records) {
                    $result = app(BulkApprovePlansAction::class)->execute($records, auth()->user());
                    $this->sendNotification($result);
                });
            }
            
            return $action;
        }, $baseActions);
    }

    /**
     * Helper method to send consistent notifications from Action results
     */
    private function sendNotification(array $result): void
    {
        $notification = Notification::make()
            ->title($this->getNotificationTitle($result['type']))
            ->body($result['message']);

        match ($result['type']) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        $notification->send();
    }

    /**
     * Get notification title based on type
     */
    private function getNotificationTitle(string $type): string
    {
        return match ($type) {
            'success' => 'Success',
            'warning' => 'Warning',
            'danger' => 'Error',
            default => 'Information',
        };
    }
}