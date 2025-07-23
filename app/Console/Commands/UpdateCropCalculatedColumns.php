<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Crop;
use App\Observers\CropObserver;
use Illuminate\Support\Facades\DB;

class UpdateCropCalculatedColumns extends Command
{
    protected $signature = 'crops:update-calculated-columns';
    protected $description = 'Update calculated columns for all existing crop records';

    public function handle()
    {
        $this->info('This command is deprecated.');
        $this->info('Calculated columns have been moved to the crop_batches_list_view database view.');
        $this->info('These values are now calculated dynamically and do not need to be updated.');
        return 0;
    }
} 