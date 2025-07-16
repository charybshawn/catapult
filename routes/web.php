<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Invoice routes
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    
    // CSV Export download route
    Route::get('/csv/download/{filename}', function ($filename) {
        $csvService = new \App\Services\CsvExportService();
        $filePath = $csvService->getFilePath($filename);
        
        if (!file_exists($filePath)) {
            abort(404, 'Export file not found');
        }
        
        return response()->download($filePath)->deleteFileAfterSend();
    })->name('csv.download');
    
    // Crop planning routes
    Route::get('/crop-planning/pdf', [\App\Http\Controllers\CropPlanningController::class, 'generatePdf'])->name('crop-planning.pdf');
    
    // Dashboard AJAX endpoint
    Route::get('/admin/dashboard/data', [\App\Filament\Pages\Dashboard::class, 'getDashboardDataAjax'])->name('dashboard.data');
    
    // Dashboard crop stage actions
    Route::post('/admin/dashboard/advance-crops', [\App\Filament\Pages\Dashboard::class, 'advanceCropsFromAlert'])->name('dashboard.advance-crops');
    Route::post('/admin/dashboard/rollback-crops', [\App\Filament\Pages\Dashboard::class, 'rollbackCropFromAlert'])->name('dashboard.rollback-crops');
    
    // Crop details and stage management
    Route::get('/admin/crops/{crop}/details', function ($cropId) {
        try {
            $details = \App\Filament\Resources\CropResource::getCropDetails($cropId);
            return response()->json($details);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    })->name('crops.details');
    
    Route::post('/admin/crops/{crop}/advance-stage', function ($cropId) {
        try {
            $crop = \App\Models\Crop::findOrFail($cropId);
            $taskManagementService = app(\App\Services\CropTaskManagementService::class);
            $taskManagementService->advanceStage($crop);
            
            return response()->json([
                'success' => true,
                'message' => 'Stage advanced successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    })->name('crops.advance-stage');
    
    Route::post('/admin/crops/{crop}/rollback-stage', function ($cropId) {
        try {
            $crop = \App\Models\Crop::with('currentStage')->findOrFail($cropId);
            
            // Get current stage
            $currentStageCode = $crop->getRelationValue('currentStage')?->code;
            if (!$currentStageCode) {
                throw new \Exception('Cannot determine current stage');
            }
            
            // Get previous stage
            $stages = [
                'germination' => null,
                'blackout' => 'germination',
                'light' => 'blackout',
                'harvested' => 'light',
            ];
            
            $previousStageCode = $stages[$currentStageCode] ?? null;
            if (!$previousStageCode) {
                throw new \Exception('Cannot rollback from current stage');
            }
            
            // Update crop stage
            $previousStageRecord = \App\Models\CropStage::where('code', $previousStageCode)->first();
            if ($previousStageRecord) {
                $crop->update([
                    'current_stage_id' => $previousStageRecord->id,
                    "{$previousStageCode}_at" => now(),
                ]);
            } else {
                throw new \Exception('Previous stage not found');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Stage rolled back successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    })->name('crops.rollback-stage');
    
    // Admin-specific routes that need Filament middleware
    Route::middleware(['web'])->prefix('admin')->group(function () {
        Route::post('/generate-crop-plan/{order}', [\App\Http\Controllers\CropPlanningController::class, 'generateCropPlan'])->name('crop-planning.generate');
    });
    
    
});

require __DIR__.'/auth.php';