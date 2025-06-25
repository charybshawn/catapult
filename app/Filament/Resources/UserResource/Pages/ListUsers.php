<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Filament\Forms;
use Illuminate\Support\Facades\Hash;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


    public function editUserModal($userId)
    {
        try {
            Log::info('Edit user modal method called', ['userId' => $userId]);
            Log::info('Current mounted table actions before:', ['actions' => $this->mountedTableActions]);
            
            // Close any existing mounted actions first
            $this->mountedTableActions = [];
            $this->mountedTableActionsData = [];
            $this->mountedTableActionsArguments = [];
            
            Log::info('Cleared mounted actions');
            
            // Now mount the edit action
            $this->mountTableAction('edit', $userId);
            Log::info('Current mounted table actions after:', ['actions' => $this->mountedTableActions]);
            Log::info('Table action mounted successfully');
        } catch (\Exception $e) {
            Log::error('Error in editUserModal method', ['error' => $e->getMessage(), 'userId' => $userId, 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
} 