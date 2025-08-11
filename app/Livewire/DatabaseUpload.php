<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class DatabaseUpload extends Component
{
    use WithFileUploads;
    
    public $file;
    public $uploadProgress = 0;
    public $uploadComplete = false;
    public $uploadedFileName = '';
    
    protected $rules = [
        'file' => 'required|file|max:204800', // 200MB
    ];
    
    public function updatedFile()
    {
        $this->uploadProgress = 0;
        $this->uploadComplete = false;
    }
    
    public function save()
    {
        $this->validate();
        
        // Save the file
        $filename = 'uploaded_' . now()->format('Y-m-d_H-i-s') . '_' . $this->file->getClientOriginalName();
        $path = $this->file->storeAs('database/backups', $filename, 'local');
        
        $this->uploadedFileName = $filename;
        $this->uploadComplete = true;
        $this->uploadProgress = 100;
        
        // Emit event to parent component
        $this->dispatch('fileUploaded', $filename);
    }
    
    public function render()
    {
        return <<<'blade'
            <div>
                <form wire:submit="save">
                    <div class="space-y-4">
                        @if (!$uploadComplete)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Upload SQL Backup File (Max 200MB)
                                </label>
                                <input type="file" 
                                       wire:model="file" 
                                       accept=".sql"
                                       class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                @error('file') 
                                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                                @enderror
                            </div>
                            
                            <div wire:loading wire:target="file" class="text-blue-600">
                                Uploading file... Please wait...
                            </div>
                            
                            @if ($file)
                                <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded">
                                    <p class="text-sm">
                                        <strong>File:</strong> {{ $file->getClientOriginalName() }}<br>
                                        <strong>Size:</strong> {{ round($file->getSize() / 1024 / 1024, 2) }} MB
                                    </p>
                                </div>
                                
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                    Upload File
                                </button>
                            @endif
                        @else
                            <div class="bg-green-100 dark:bg-green-900 p-4 rounded">
                                <p class="text-green-800 dark:text-green-200">
                                    ✅ File uploaded successfully!<br>
                                    <strong>Filename:</strong> {{ $uploadedFileName }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    You can now close this dialog and select "Select Existing Backup" to use this file.
                                </p>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        blade;
    }
}