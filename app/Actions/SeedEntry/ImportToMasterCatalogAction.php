<?php

namespace App\Actions\SeedEntry;

use App\Models\SeedEntry;
use App\Models\MasterSeedCatalog;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pure business logic for importing seed entries to master catalog
 */
class ImportToMasterCatalogAction
{
    public function execute(Collection $seedEntries): array
    {
        $imported = 0;
        $updated = 0;
        $errors = [];
        
        foreach ($seedEntries as $seedEntry) {
            try {
                $result = $this->processSingleEntry($seedEntry);
                
                if ($result['created']) {
                    $imported++;
                } elseif ($result['updated']) {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "{$seedEntry->common_name} - {$seedEntry->cultivar_name}: " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
    
    protected function processSingleEntry(SeedEntry $seedEntry): array
    {
        // Find existing master catalog entry by common name
        $masterCatalog = MasterSeedCatalog::where('common_name', $seedEntry->common_name)
            ->first();
        
        if ($masterCatalog) {
            return $this->updateExistingEntry($masterCatalog, $seedEntry);
        } else {
            return $this->createNewEntry($seedEntry);
        }
    }
    
    protected function updateExistingEntry(MasterSeedCatalog $masterCatalog, SeedEntry $seedEntry): array
    {
        // Update existing entry - add cultivar if not already present
        $cultivars = $masterCatalog->cultivars ?? [];
        
        // Check if cultivar already exists (case-insensitive)
        $cultivarExists = false;
        foreach ($cultivars as $existing) {
            if (strcasecmp(trim($existing), trim($seedEntry->cultivar_name)) === 0) {
                $cultivarExists = true;
                break;
            }
        }
        
        if (!$cultivarExists) {
            $cultivars[] = $seedEntry->cultivar_name;
            $masterCatalog->update([
                'cultivars' => array_values(array_unique($cultivars))
            ]);
            return ['created' => false, 'updated' => true];
        }
        
        return ['created' => false, 'updated' => false];
    }
    
    protected function createNewEntry(SeedEntry $seedEntry): array
    {
        // Create new master catalog entry
        MasterSeedCatalog::create([
            'common_name' => $seedEntry->common_name,
            'cultivars' => [$seedEntry->cultivar_name],
            'description' => $seedEntry->description,
            'is_active' => true,
        ]);
        
        return ['created' => true, 'updated' => false];
    }
}