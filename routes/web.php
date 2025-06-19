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
    
    // Crop planning routes
    Route::get('/crop-planning/pdf', [\App\Http\Controllers\CropPlanningController::class, 'generatePdf'])->name('crop-planning.pdf');
    
    // Dashboard AJAX endpoint
    Route::get('/admin/dashboard/data', [\App\Filament\Pages\Dashboard::class, 'getDashboardDataAjax'])->name('dashboard.data');
    
    // Database backup download route
    Route::get('/admin/database/backup/download/{filename}', function (string $filename) {
        $backupService = new \App\Services\DatabaseBackupService();
        $filePath = $backupService->downloadBackup($filename);
        
        if ($filePath && file_exists($filePath)) {
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/sql',
            ]);
        }
        
        abort(404, 'Backup file not found');
    })->name('database.backup.download');
    
    // Data export download route
    Route::get('/admin/data-export/{export}/download', function (\App\Models\DataExport $export) {
        if (!$export->fileExists()) {
            abort(404, 'Export file not found');
        }
        
        return response()->download($export->filepath);
    })->name('filament.admin.data-export.download');
});

require __DIR__.'/auth.php';
