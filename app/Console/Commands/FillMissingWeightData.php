<?php

namespace App\Console\Commands;

use App\Models\SeedVariation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillMissingWeightData extends Command
{
    protected $signature = 'seed:fill-missing-weights {--dry-run : Show what would be filled without making changes} {--auto-fill : Automatically fill weights without prompting}';
    
    protected $description = 'Fill in missing weight information for seed variations based on size descriptions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $autoFill = $this->option('auto-fill');
        
        $this->info('Scanning for missing weight information...');
        
        // Find variations missing weight_kg but with size descriptions
        $missingWeights = SeedVariation::whereNull('weight_kg')
            ->whereNotNull('size_description')
            ->where('size_description', '!=', '')
            ->with('seedEntry')
            ->get();
            
        if ($missingWeights->isEmpty()) {
            $this->info('No missing weight data found!');
            return;
        }
        
        $this->warn(sprintf('Found %d variations missing weight data:', $missingWeights->count()));
        $this->newLine();
        
        $totalFilled = 0;
        $patterns = $this->getWeightPatterns();
        
        foreach ($missingWeights as $variation) {
            $sizeDesc = $variation->size_description;
            $extractedWeight = $this->extractWeightFromDescription($sizeDesc, $patterns);
            
            if (!$extractedWeight) {
                continue;
            }
            
            $this->line(sprintf(
                'Variation ID: %d - "%s" - Entry: %s - %s',
                $variation->id,
                $sizeDesc,
                $variation->seedEntry->common_name ?? 'Unknown',
                $variation->seedEntry->cultivar_name ?? 'Unknown'
            ));
            
            $this->line(sprintf(
                '  Detected: %.4f kg (from %s %s)',
                $extractedWeight['weight_kg'],
                $extractedWeight['original_value'],
                $extractedWeight['original_unit']
            ));
            
            if ($dryRun) {
                $this->line('  [DRY RUN] Would fill this weight data');
                $this->newLine();
                continue;
            }
            
            if (!$autoFill) {
                if (!$this->confirm('Fill this weight data?', true)) {
                    $this->line('  Skipped');
                    $this->newLine();
                    continue;
                }
            }
            
            // Update the variation
            $variation->update([
                'weight_kg' => $extractedWeight['weight_kg'],
                'original_weight_value' => $extractedWeight['original_value'],
                'original_weight_unit' => $extractedWeight['original_unit']
            ]);
            
            $this->info(sprintf('  ✓ Updated variation ID: %d', $variation->id));
            $totalFilled++;
            $this->newLine();
        }
        
        if ($dryRun) {
            $this->info(sprintf('DRY RUN: Would fill %d missing weight entries', 
                $missingWeights->filter(fn($v) => $this->extractWeightFromDescription($v->size_description, $patterns))->count()));
        } else {
            $this->info(sprintf('Successfully filled %d missing weight entries!', $totalFilled));
        }
        
        // Show summary of what couldn't be parsed
        $unparseable = $missingWeights->filter(fn($v) => !$this->extractWeightFromDescription($v->size_description, $patterns));
        
        if ($unparseable->isNotEmpty()) {
            $this->newLine();
            $this->warn('Size descriptions that could not be automatically parsed:');
            foreach ($unparseable->take(10) as $variation) {
                $this->line(sprintf('  "%s" (ID: %d)', $variation->size_description, $variation->id));
            }
            
            if ($unparseable->count() > 10) {
                $this->line(sprintf('  ... and %d more', $unparseable->count() - 10));
            }
            
            $this->line('These may need manual review and weight assignment.');
        }
    }
    
    /**
     * Get weight conversion patterns
     */
    private function getWeightPatterns(): array
    {
        return [
            // Metric patterns
            '/(\d+(?:\.\d+)?)\s*(?:kilos?|kgs?|k)\b/i' => ['multiplier' => 1.0, 'unit' => 'kg'],
            '/(\d+(?:\.\d+)?)\s*(?:grams?|gms?|g)\b/i' => ['multiplier' => 0.001, 'unit' => 'grams'],
            '/(\d+(?:\.\d+)?)\s*(?:milligrams?|mgs?|mg)\b/i' => ['multiplier' => 0.000001, 'unit' => 'mg'],
            
            // Imperial patterns  
            '/(\d+(?:\.\d+)?)\s*(?:pounds?|lbs?|lb)\b/i' => ['multiplier' => 0.453592, 'unit' => 'lbs'],
            '/(\d+(?:\.\d+)?)\s*(?:ounces?|ozs?|oz)\b/i' => ['multiplier' => 0.0283495, 'unit' => 'oz'],
            
            // Special patterns
            '/(\d+(?:\.\d+)?)\s*(?:seeds?)\b/i' => ['multiplier' => null, 'unit' => 'seeds'], // Can't convert seeds to weight
        ];
    }
    
    /**
     * Extract weight information from size description
     */
    private function extractWeightFromDescription(string $description, array $patterns): ?array
    {
        foreach ($patterns as $pattern => $config) {
            if (preg_match($pattern, $description, $matches)) {
                $value = (float) $matches[1];
                
                // Skip seed counts as we can't convert to weight
                if ($config['unit'] === 'seeds') {
                    continue;
                }
                
                $weightKg = $value * $config['multiplier'];
                
                return [
                    'weight_kg' => round($weightKg, 4),
                    'original_value' => $value,
                    'original_unit' => $config['unit']
                ];
            }
        }
        
        return null;
    }
}