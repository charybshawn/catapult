<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix common names with specific mappings
        $mappings = [
            'Greencrops' => 'Pea',
            'Eldorado' => 'Chard',
            'Ruby Red' => 'Chard',
            'Early Wonder AC Strain' => 'Beet',
            'Bull\'s Blood' => 'Beet',
            'Brussels Winter Vertissimo' => 'Brussels Sprouts',
            'Dwarf Grey Sugar' => 'Pea',
            'Oregon Sugar Pod II' => 'Pea',
            'Evergreen Long White Nebuka' => 'Onion',
            'Finnochio/Florence' => 'Fennel',
            'Green de Belleville' => 'Lentil',
            'Greens,' => 'Greens',
            'Greens, Garden Cress' => 'Cress',
            'Greens, Red Garnet' => 'Greens',
            'Radish' => 'Radish',
            'Red Rubin Improved' => 'Basil',
            'Santo' => 'Cilantro',
            'White Vienna' => 'Kohlrabi',
            'Winter , Minowase-Diakon' => 'Radish',
            'Aladdin pvp' => 'Spinach',
            'Bouquet' => 'Dill',
        ];
        
        // Update seed_entries
        foreach ($mappings as $cultivar => $common) {
            DB::table('seed_entries')
                ->where('cultivar_name', $cultivar)
                ->update(['common_name' => $common]);
        }
        
        // Update recipes if they have cultivar data
        foreach ($mappings as $cultivar => $common) {
            DB::table('recipes')
                ->where('cultivar_name', $cultivar)
                ->update(['common_name' => $common]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - this is a data fix
    }
};