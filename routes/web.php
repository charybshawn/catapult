<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test route for Debugbar
Route::get('/debugbar-test', function () {
    \Debugbar::info('Debugbar is working!');
    \Debugbar::error('This is an error message');
    \Debugbar::warning('This is a warning');
    \Debugbar::addMessage('Custom message', 'custom');
    
    // Test database queries
    $crops = \App\Models\Crop::with('recipe')->take(5)->get();
    
    return view('welcome', [
        'message' => 'Laravel Debugbar is installed and working!',
        'crops' => $crops
    ]);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Test route for CropBatch query optimization
Route::get('/test-crop-batch-optimization', function () {
    \DB::enableQueryLog();
    
    // Test 1: Original approach (without CropBatch optimization)
    $startTime1 = microtime(true);
    $crops = \App\Models\Crop::with(['recipe', 'currentStage'])
        ->groupBy('recipe_id', 'planting_at', 'current_stage_id')
        ->selectRaw('
            MIN(id) as id,
            recipe_id,
            planting_at,
            current_stage_id,
            COUNT(*) as crop_count,
            GROUP_CONCAT(tray_number) as tray_numbers
        ')
        ->limit(10)
        ->get();
    $endTime1 = microtime(true);
    $queries1 = count(\DB::getQueryLog());
    
    // Clear query log for next test
    \DB::flushQueryLog();
    
    // Test 2: New approach with CropBatch
    $startTime2 = microtime(true);
    $batches = \App\Models\CropBatch::withFullDetails()
        ->limit(10)
        ->get();
    $endTime2 = microtime(true);
    $queries2 = count(\DB::getQueryLog());
    
    // Calculate results
    $time1 = round(($endTime1 - $startTime1) * 1000, 2); // milliseconds
    $time2 = round(($endTime2 - $startTime2) * 1000, 2); // milliseconds
    
    return response()->json([
        'original_approach' => [
            'query_count' => $queries1,
            'execution_time_ms' => $time1,
            'record_count' => $crops->count()
        ],
        'cropbatch_approach' => [
            'query_count' => $queries2,
            'execution_time_ms' => $time2,
            'record_count' => $batches->count()
        ],
        'improvement' => [
            'query_reduction' => $queries1 - $queries2,
            'time_saved_ms' => round($time1 - $time2, 2),
            'percentage_faster' => $time1 > 0 ? round((($time1 - $time2) / $time1) * 100, 2) . '%' : 'N/A'
        ]
    ]);
});

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
    
    // Simple upload for large SQL files (workaround for Filament upload issues)
    Route::post('/admin/simple-upload', [\App\Http\Controllers\SimpleUploadController::class, 'upload'])->name('simple.upload');
    
    
    // Admin-specific routes that need Filament middleware
    Route::middleware(['web'])->prefix('admin')->group(function () {
        Route::post('/generate-crop-plan/{order}', [\App\Http\Controllers\CropPlanningController::class, 'generateCropPlan'])->name('crop-planning.generate');
    });
    
    
});


require __DIR__.'/auth.php';