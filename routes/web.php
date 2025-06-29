<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Debug route to test login POST
Route::post('/debug-login', function (Illuminate\Http\Request $request) {
    \Log::info('Debug login POST received', [
        'data' => $request->all(),
        'headers' => $request->headers->all()
    ]);
    return response()->json(['status' => 'received', 'data' => $request->all()]);
});

// Debug route to test CSRF
Route::get('/debug-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'session_driver' => config('session.driver'),
        'app_url' => config('app.url'),
        'session_domain' => config('session.domain'),
        'session_secure' => config('session.secure'),
        'is_https' => request()->isSecure(),
        'headers' => request()->headers->all()
    ]);
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
    
    // Admin-specific routes that need Filament middleware
    Route::middleware(['web'])->prefix('admin')->group(function () {
        Route::post('/generate-crop-plan/{order}', [\App\Http\Controllers\CropPlanningController::class, 'generateCropPlan'])->name('crop-planning.generate');
    });
    
    
});

// Filament admin login POST route (temporary fix)
Route::middleware(['web', 'guest'])->post('/admin/login', function (Illuminate\Http\Request $request) {
    // This route handles Livewire form submissions for admin login
    return redirect()->route('filament.admin.auth.login');
})->name('filament.admin.auth.login.post');

require __DIR__.'/auth.php';