<?php

namespace App\Console\Commands;

use App\Models\SeedEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateSeedEntries extends Command
{
    protected $signature = 'seed:clean-duplicates {--dry-run : Show what would be cleaned without making changes} {--auto-merge : Automatically merge duplicates without prompting}';
    
    protected $description = 'Find and clean up duplicate seed entries based on common name, cultivar, and supplier';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $autoMerge = $this->option('auto-merge');
        
        $this->info('Scanning for duplicate seed entries...');
        
        // Find duplicates based on common_name, cultivar_name, and supplier_id
        $duplicates = DB::select("
            SELECT common_name, cultivar_name, supplier_id, COUNT(*) as count, 
                   GROUP_CONCAT(id) as ids
            FROM seed_entries 
            GROUP BY common_name, cultivar_name, supplier_id 
            HAVING COUNT(*) > 1
            ORDER BY count DESC, common_name, cultivar_name
        ");
        
        if (empty($duplicates)) {
            $this->info('No duplicate seed entries found!');
            return;
        }
        
        $this->warn(sprintf('Found %d sets of duplicate entries:', count($duplicates)));
        $this->newLine();
        
        $totalCleaned = 0;
        
        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            $entries = SeedEntry::whereIn('id', $ids)->with(['variations', 'supplier'])->get();
            
            $this->line(sprintf(
                'Duplicate: %s - %s (%s) - %d entries', 
                $duplicate->common_name,
                $duplicate->cultivar_name, 
                $entries->first()->supplier->name ?? 'Unknown Supplier',
                $duplicate->count
            ));
            
            // Show details of each duplicate
            foreach ($entries as $entry) {
                $this->line(sprintf(
                    '  ID: %d, Created: %s, Variations: %d, Title: %s',
                    $entry->id,
                    $entry->created_at->format('Y-m-d'),
                    $entry->variations->count(),
                    $entry->supplier_product_title ?: 'No title'
                ));
            }
            
            if ($dryRun) {
                $this->line('  [DRY RUN] Would merge these entries');
                $this->newLine();
                continue;
            }
            
            if (!$autoMerge) {
                if (!$this->confirm('Merge these duplicate entries?', true)) {
                    $this->line('  Skipped');
                    $this->newLine();
                    continue;
                }
            }
            
            // Merge duplicates - keep the oldest entry (lowest ID) as the primary
            $primaryEntry = $entries->sortBy('id')->first();
            $duplicateEntries = $entries->where('id', '!=', $primaryEntry->id);
            
            $this->line(sprintf('  Merging into primary entry ID: %d', $primaryEntry->id));
            
            DB::transaction(function () use ($primaryEntry, $duplicateEntries) {
                foreach ($duplicateEntries as $duplicate) {
                    // Move variations from duplicate to primary entry
                    // Check for conflicts first and merge similar variations
                    foreach ($duplicate->variations as $variation) {
                        $existingVariation = $primaryEntry->variations()
                            ->where('size_description', $variation->size_description)
                            ->first();
                            
                        if ($existingVariation) {
                            // Merge price histories and keep the best price
                            if ($variation->current_price < $existingVariation->current_price) {
                                $existingVariation->current_price = $variation->current_price;
                                $existingVariation->save();
                            }
                            
                            // Move price history
                            $variation->priceHistory()->update(['seed_variation_id' => $existingVariation->id]);
                            
                            // Delete the duplicate variation
                            $variation->delete();
                        } else {
                            // No conflict, just move the variation
                            $variation->update(['seed_entry_id' => $primaryEntry->id]);
                        }
                    }
                    
                    // Update primary entry with best available data
                    if (!$primaryEntry->supplier_product_title && $duplicate->supplier_product_title) {
                        $primaryEntry->supplier_product_title = $duplicate->supplier_product_title;
                    }
                    
                    if (!$primaryEntry->supplier_product_url && $duplicate->supplier_product_url) {
                        $primaryEntry->supplier_product_url = $duplicate->supplier_product_url;
                    }
                    
                    if (!$primaryEntry->image_url && $duplicate->image_url) {
                        $primaryEntry->image_url = $duplicate->image_url;
                    }
                    
                    if (!$primaryEntry->description && $duplicate->description) {
                        $primaryEntry->description = $duplicate->description;
                    }
                    
                    // Merge tags
                    if ($duplicate->tags) {
                        $existingTags = $primaryEntry->tags ?? [];
                        $newTags = array_unique(array_merge($existingTags, $duplicate->tags));
                        $primaryEntry->tags = $newTags;
                    }
                    
                    // Delete the duplicate entry
                    $duplicate->delete();
                }
                
                $primaryEntry->save();
            });
            
            $this->info(sprintf('  ✓ Merged %d duplicates into ID: %d', $duplicateEntries->count(), $primaryEntry->id));
            $totalCleaned += $duplicateEntries->count();
            $this->newLine();
        }
        
        if ($dryRun) {
            $this->info(sprintf('DRY RUN: Would clean up %d duplicate entries', 
                collect($duplicates)->sum(fn($d) => $d->count - 1)));
        } else {
            $this->info(sprintf('Successfully cleaned up %d duplicate entries!', $totalCleaned));
        }
        
        // Now scan for similar common names that might be typos
        $this->newLine();
        $this->info('Scanning for similar common names that might be typos...');
        
        $commonNames = SeedEntry::distinct()->pluck('common_name')->filter()->sort();
        $possibleTypos = [];
        
        foreach ($commonNames as $name1) {
            foreach ($commonNames as $name2) {
                if ($name1 !== $name2 && $this->stringSimilarity($name1, $name2) > 0.8) {
                    $key = collect([$name1, $name2])->sort()->join('|');
                    if (!isset($possibleTypos[$key])) {
                        $possibleTypos[$key] = [$name1, $name2];
                    }
                }
            }
        }
        
        if (!empty($possibleTypos)) {
            $this->warn('Found possible typos in common names:');
            foreach ($possibleTypos as $pair) {
                $count1 = SeedEntry::where('common_name', $pair[0])->count();
                $count2 = SeedEntry::where('common_name', $pair[1])->count();
                $this->line(sprintf('  "%s" (%d entries) vs "%s" (%d entries)', 
                    $pair[0], $count1, $pair[1], $count2));
            }
            $this->line('Review these manually to standardize naming.');
        }
    }
    
    /**
     * Calculate string similarity using Levenshtein distance
     */
    private function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        if ($str1 === $str2) {
            return 1.0;
        }
        
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return ($maxLen - $distance) / $maxLen;
    }
}