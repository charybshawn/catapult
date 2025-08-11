<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SimpleUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:204800', // 200MB in KB
        ]);
        
        $file = $request->file('file');
        $filename = 'uploaded_' . now()->format('Y-m-d_H-i-s') . '_' . $file->getClientOriginalName();
        
        // Save directly to database/backups folder
        $path = base_path('database/backups/' . $filename);
        $file->move(base_path('database/backups'), $filename);
        
        return response()->json([
            'success' => true,
            'filename' => $filename,
            'size' => $file->getSize(),
        ]);
    }
}