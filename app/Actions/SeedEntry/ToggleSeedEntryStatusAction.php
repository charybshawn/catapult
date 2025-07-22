<?php

namespace App\Actions\SeedEntry;

use App\Models\SeedEntry;

/**
 * Pure business logic for toggling seed entry active status
 */
class ToggleSeedEntryStatusAction
{
    public function activate(SeedEntry $seedEntry): void
    {
        $seedEntry->update(['is_active' => true]);
    }
    
    public function deactivate(SeedEntry $seedEntry): void
    {
        $seedEntry->update(['is_active' => false]);
    }
}