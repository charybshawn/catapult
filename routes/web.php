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
    
    // Admin-specific routes that need Filament middleware
    Route::middleware(['web'])->prefix('admin')->group(function () {
        Route::post('/generate-crop-plan/{order}', [\App\Http\Controllers\CropPlanningController::class, 'generateCropPlan'])->name('crop-planning.generate');
    });
    
    
});

require __DIR__.'/auth.php';