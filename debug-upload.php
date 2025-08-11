<?php
// Debug script to test file upload validation
// Run with: php debug-upload.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

echo "=== File Upload Debugging ===\n\n";

// Check PHP limits
echo "PHP Configuration:\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n";
echo "- max_execution_time: " . ini_get('max_execution_time') . "\n\n";

// Check Livewire config
echo "Livewire Configuration:\n";
$livewireRules = config('livewire.temporary_file_upload.rules');
echo "- rules: " . json_encode($livewireRules) . "\n";
echo "- max_upload_time: " . config('livewire.temporary_file_upload.max_upload_time') . " minutes\n\n";

// File size calculations
$fileSizeMB = 33;  // Your file size
$fileSizeKB = $fileSizeMB * 1024;
$configLimitKB = 204800;  // Your config limit

echo "File Size Analysis:\n";
echo "- Your file: {$fileSizeMB}MB ({$fileSizeKB}KB)\n";
echo "- Config limit: " . ($configLimitKB/1024) . "MB ({$configLimitKB}KB)\n";
echo "- Within limit: " . ($fileSizeKB <= $configLimitKB ? "YES ✅" : "NO ❌") . "\n\n";

// Test Laravel validation rules
echo "Testing Laravel Validation:\n";
$validator = validator(['file_size' => $fileSizeKB], ['file_size' => 'max:204800']);
if ($validator->passes()) {
    echo "- Laravel validation: PASSES ✅\n";
} else {
    echo "- Laravel validation: FAILS ❌\n";
    echo "  Errors: " . json_encode($validator->errors()->all()) . "\n";
}

echo "\n=== Debug Complete ===\n";