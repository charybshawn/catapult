# CSV Export Module for Filament Resources

This module provides an easy way to add CSV export functionality to any Filament resource with a configurable action button on list pages.

## Quick Setup

### 1. Add the Trait to Your Resource

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Traits\CsvExportAction;
use Filament\Resources\Resource;

class YourResource extends Resource
{
    use CsvExportAction;
    
    // ... your existing code
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // your columns
            ])
            ->headerActions([
                static::getCsvExportAction(), // Add this line
            ]);
    }
}
```

### 2. Basic Usage (No Customization Required)

The module will automatically detect your model's fillable fields and common columns like timestamps. It works out of the box with any Filament resource.

## Advanced Configuration

### Custom Column Selection

Override the `getCsvExportColumns()` method to define exactly which columns should be available for export:

```php
protected static function getCsvExportColumns(): array
{
    return [
        'id' => 'ID',
        'name' => 'Product Name',
        'email' => 'Email Address',
        'status' => 'Status',
        'created_at' => 'Created Date',
        'updated_at' => 'Updated Date',
    ];
}
```

### Including Relationship Data

To include related model data in exports:

```php
protected static function getCsvExportColumns(): array
{
    return static::addRelationshipColumns([
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
    ], [
        'category' => ['name'],                    // Single field
        'user' => ['name', 'email'],              // Multiple fields
        'profile.address' => ['city', 'state'],   // Nested relationships
    ]);
}

protected static function getCsvExportRelationships(): array
{
    return ['category', 'user.profile']; // Preload these relationships
}
```

### Custom Query Modification

If you need to modify the export query (e.g., to apply specific filters), override the `getTableQuery()` method:

```php
protected static function getTableQuery(): Builder
{
    return static::getModel()::query()
        ->where('active', true)
        ->with(['category', 'tags']);
}
```

## Features

### User Interface
- **Export Button**: Appears in the table header actions
- **Column Selection**: Users can choose which columns to include
- **Filename Customization**: Users can specify a custom filename
- **Relationship Toggle**: Option to include related data (if configured)

### File Handling
- **Automatic Naming**: Generates descriptive filenames with timestamps
- **Secure Downloads**: Files are stored temporarily and cleaned up automatically
- **Large Dataset Support**: Efficient memory usage for large exports

### Data Formatting
- **Smart Type Handling**: 
  - Dates formatted as "Y-m-d H:i:s"
  - Booleans as "Yes/No"
  - JSON/Arrays as JSON strings
  - Null values as empty strings
- **Relationship Data**: Dot notation support for nested relationships
- **UTF-8 Encoding**: Proper handling of international characters

## Complete Example

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Traits\CsvExportAction;
use App\Models\Product;
use Filament\Resources\Resource;

class ProductResource extends Resource
{
    use CsvExportAction;
    
    protected static ?string $model = Product::class;
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('category.name'),
                TextColumn::make('price'),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }
    
    /**
     * Define which columns are available for CSV export
     */
    protected static function getCsvExportColumns(): array
    {
        return static::addRelationshipColumns([
            'id' => 'Product ID',
            'name' => 'Product Name',
            'description' => 'Description',
            'price' => 'Price',
            'active' => 'Is Active',
            'created_at' => 'Created Date',
        ], [
            'category' => ['name'],
            'supplier' => ['name', 'email'],
            'tags' => ['name'],
        ]);
    }
    
    /**
     * Define which relationships to preload for export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['category', 'supplier', 'tags'];
    }
}
```

## Technical Details

### File Storage
- Files are stored in `storage/app/exports/`
- Temporary files are automatically deleted after download
- Old files are cleaned up automatically (configurable)

### Performance
- Uses Laravel's query builder for efficient data retrieval
- Streams CSV output to handle large datasets
- Only loads requested relationships to minimize memory usage

### Security
- Download URLs are protected by authentication
- Files are validated before serving
- Temporary file storage prevents accumulation

## Troubleshooting

### Common Issues

1. **Missing Download Route**: Make sure the CSV download route is added to your `routes/web.php`:
   ```php
   Route::get('/csv/download/{filename}', function ($filename) {
       $csvService = new \App\Services\CsvExportService();
       $filePath = $csvService->getFilePath($filename);
       
       if (!file_exists($filePath)) {
           abort(404, 'Export file not found');
       }
       
       return response()->download($filePath)->deleteFileAfterSend();
   })->name('csv.download');
   ```

2. **Permission Errors**: Ensure the `storage/app/exports/` directory is writable
3. **Memory Issues**: For very large datasets, consider implementing pagination in the export service

### Debug Mode

Enable logging by adding this to your resource:

```php
protected static function getTableQuery(): Builder
{
    $query = parent::getTableQuery();
    \Log::info('CSV Export Query: ' . $query->toSql());
    return $query;
}
```