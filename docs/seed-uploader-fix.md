# Seed Uploader Process Flow and Fix

## Issue Summary
The JSON file uploads were successfully uploaded to the `storage/app/private/livewire-tmp` directory, but the queue worker wasn't processing them properly due to path discrepancies between where Livewire temporarily stored the files and where the SeedScrapeImporter expected to find them.

## Process Flow

### Original (Broken) Flow:
1. User uploads JSON file through Filament uploader
2. Livewire puts file in `storage/app/private/livewire-tmp/` with a temporary name
3. Uploader component tries to move file to `storage/app/seed-scrape-uploads/`
4. Queue job is dispatched with path to file in `storage/app/seed-scrape-uploads/`
5. File not found at expected location, causing the job to fail silently

### Fixed Flow:
1. User uploads JSON file through Filament uploader
2. Livewire puts file in `storage/app/private/livewire-tmp/` with a temporary name
3. Uploader component reads file content from temp location
4. Uploader component manually writes content to `storage/app/seed-scrape-uploads/`
5. Queue job is dispatched with path to file in `storage/app/seed-scrape-uploads/`
6. Job processes file successfully

## Testing the Fix
1. The fix was verified by manually copying a JSON file to the expected location
2. Running the test command `php artisan test:seed-import` processed the file successfully
3. After implementing the fix in the Filament uploader, future uploads should work properly

## Troubleshooting Similar Issues
If uploads are not being processed, check:
1. File paths in the uploaded file handler
2. Queue worker is running (`php artisan queue:work`)
3. Log files for errors
4. Test with manual file processing to isolate the issue 