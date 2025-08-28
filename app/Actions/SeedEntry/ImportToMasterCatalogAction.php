<?php

namespace App\Actions\SeedEntry;

use Exception;
use App\Models\MasterCultivar;
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
            } catch (Exception $e) {
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
        // Check if cultivar already exists for this catalog (case-insensitive)
        $cultivarExists = MasterCultivar::where('master_seed_catalog_id', $masterCatalog->id)
            ->whereRaw('LOWER(cultivar_name) = ?', [strtolower(trim($seedEntry->cultivar_name))])
            ->exists();
        
        if (!$cultivarExists) {
            // Create new cultivar for this catalog
            MasterCultivar::create([
                'master_seed_catalog_id' => $masterCatalog->id,
                'cultivar_name' => $seedEntry->cultivar_name,
                'is_active' => true,
            ]);
            return ['created' => false, 'updated' => true];
        }
        
        return ['created' => false, 'updated' => false];
    }
    
    protected function createNewEntry(SeedEntry $seedEntry): array
    {
        // Create new cultivar first
        $cultivar = MasterCultivar::create([
            'master_seed_catalog_id' => null, // Will be set below
            'cultivar_name' => $seedEntry->cultivar_name,
            'is_active' => true,
        ]);
        
        // Create new master catalog entry with reference to cultivar
        $masterCatalog = MasterSeedCatalog::create([
            'common_name' => $seedEntry->common_name,
            'cultivar_id' => $cultivar->id,
            'description' => $seedEntry->description,
            'is_active' => true,
        ]);
        
        // Update cultivar with the catalog ID
        $cultivar->update(['master_seed_catalog_id' => $masterCatalog->id]);
        
        return ['created' => true, 'updated' => false];
    }
}