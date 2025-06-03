# Seed Inventory & Pricing Management System

## Overview
This document outlines the implementation plan for a Filament PHP-based systemthat will track seed availability, pricing history, and supplier information from scraped JSON data to enable informed purchasing decisions.

## Objectives
1. A simple to use tracking system that provides pricing and availability data from various seed supplier websites.
2. Provide historical data to track trends using graphs and charts
3. Data is obtained by uploading json files containing scraped data from the supplier sites
4. These files are processed by queues and important into the revelant database tables.

## Database Schema Integration

### Existing Tables to Leverage
- We'll use the existing `suppliers` table instead of creating a new one

### New Migrations to Create

1. **Cultivars Table**
```php
Schema::create('seed_cultivars', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});
```

2. **Seed Entries Table**
```php
Schema::create('seed_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seed_cultivar_id')->constrained()->onDelete('cascade');
    $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
    $table->string('supplier_product_title');
    $table->string('supplier_product_url');
    $table->string('image_url')->nullable();
    $table->text('description')->nullable();
    $table->json('tags')->nullable();
    $table->unique(['supplier_id', 'supplier_product_url']);
    $table->timestamps();
});
```

3. **Seed Variations Table**
```php
Schema::create('seed_variations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seed_entry_id')->constrained()->onDelete('cascade');
    $table->string('size_description');
    $table->string('sku')->nullable();
    $table->decimal('weight_kg', 10, 4)->nullable();
    $table->string('original_weight_value')->nullable();
    $table->string('original_weight_unit')->nullable();
    $table->decimal('current_price', 10, 2);
    $table->boolean('is_in_stock')->default(true);
    $table->timestamp('last_checked_at');
    $table->timestamps();
    $table->unique(['seed_entry_id', 'size_description']);
});
```

4. **Seed Price History Table**
```php
Schema::create('seed_price_history', function (Blueprint $table) {
    $table->id();
    $table->foreignId('seed_variation_id')->constrained()->onDelete('cascade');
    $table->decimal('price', 10, 2);
    $table->boolean('is_in_stock');
    $table->timestamp('scraped_at');
    $table->timestamps();
});
```

5. **Scrape Uploads Table**
```php
Schema::create('seed_scrape_uploads', function (Blueprint $table) {
    $table->id();
    $table->string('original_filename');
    $table->string('status'); // pending, processing, completed, error
    $table->timestamp('uploaded_at');
    $table->timestamp('processed_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

## Integration with Existing Consumables System

We'll create a connection between our seed variations and the existing consumables system:

```php
// Add to seed_variations migration or create a separate migration
Schema::table('seed_variations', function (Blueprint $table) {
    $table->foreignId('consumable_id')->nullable()->constrained()->onDelete('set null');
});
```

## Models

### SeedCultivar Model
```php
class SeedCultivar extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function seedEntries()
    {
        return $this->hasMany(SeedEntry::class);
    }
    
    public function suppliers()
    {
        return $this->hasManyThrough(Supplier::class, SeedEntry::class, 'seed_cultivar_id', 'id', 'id', 'supplier_id')
            ->distinct();
    }
}
```

### SeedEntry Model
```php
class SeedEntry extends Model
{
    protected $fillable = [
        'seed_cultivar_id', 'supplier_id', 'supplier_product_title', 
        'supplier_product_url', 'image_url', 'description', 'tags'
    ];
    
    protected $casts = [
        'tags' => 'array',
    ];
    
    public function seedCultivar()
    {
        return $this->belongsTo(SeedCultivar::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function variations()
    {
        return $this->hasMany(SeedVariation::class);
    }
}
```

### SeedVariation Model
```php
class SeedVariation extends Model
{
    protected $fillable = [
        'seed_entry_id', 'size_description', 'sku', 'weight_kg',
        'original_weight_value', 'original_weight_unit',
        'current_price', 'is_in_stock', 'last_checked_at', 'consumable_id'
    ];
    
    protected $casts = [
        'weight_kg' => 'decimal:4',
        'current_price' => 'decimal:2',
        'is_in_stock' => 'boolean',
        'last_checked_at' => 'datetime',
    ];
    
    public function seedEntry()
    {
        return $this->belongsTo(SeedEntry::class);
    }
    
    public function priceHistory()
    {
        return $this->hasMany(SeedPriceHistory::class);
    }
    
    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
    
    public function getPricePerKgAttribute()
    {
        if ($this->weight_kg && $this->weight_kg > 0) {
            return $this->current_price / $this->weight_kg;
        }
        return null;
    }
}
```

### SeedPriceHistory Model
```php
class SeedPriceHistory extends Model
{
    protected $fillable = ['seed_variation_id', 'price', 'is_in_stock', 'scraped_at'];
    
    protected $casts = [
        'price' => 'decimal:2',
        'is_in_stock' => 'boolean',
        'scraped_at' => 'datetime',
    ];
    
    public function seedVariation()
    {
        return $this->belongsTo(SeedVariation::class);
    }
}
```

### SeedScrapeUpload Model
```php
class SeedScrapeUpload extends Model
{
    protected $fillable = ['original_filename', 'status', 'uploaded_at', 'processed_at', 'notes'];
    
    protected $casts = [
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
```

## Filament Resources

### SeedCultivarResource
```php
public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('name')
            ->required()
            ->unique(ignoreRecord: true),
        Textarea::make('description')
            ->nullable(),
    ]);
}

public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('name')
            ->searchable()
            ->sortable(),
        TextColumn::make('seedEntries_count')
            ->counts('seedEntries')
            ->sortable(),
        TextColumn::make('suppliers_count')
            ->counts('suppliers')
            ->sortable(),
    ]);
}
```

### SeedVariationResource
```php
public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('seedEntry.seedCultivar.name')
            ->label('Cultivar')
            ->searchable()
            ->sortable()
            ->toggleable(),
        TextColumn::make('seedEntry.supplier_product_title')
            ->label('Product Title')
            ->searchable()
            ->sortable()
            ->toggleable(),
        TextColumn::make('seedEntry.supplier.name')
            ->label('Supplier')
            ->searchable()
            ->sortable()
            ->toggleable(),
        TextColumn::make('size_description')
            ->searchable()
            ->sortable(),
        TextColumn::make('weight_kg')
            ->numeric(decimals: 3)
            ->sortable(),
        TextColumn::make('current_price')
            ->money('USD')
            ->sortable(),
        TextColumn::make('price_per_kg')
            ->money('USD')
            ->state(function (SeedVariation $record): ?float {
                return $record->price_per_kg;
            })
            ->sortable(query: function (Builder $query, string $direction): Builder {
                return $query->orderByRaw('current_price / NULLIF(weight_kg, 0) ' . $direction);
            }),
        IconColumn::make('is_in_stock')
            ->boolean()
            ->sortable(),
        TextColumn::make('last_checked_at')
            ->dateTime()
            ->sortable(),
        TextColumn::make('consumable.quantity')
            ->label('Current Stock')
            ->sortable(),
    ])
    ->defaultGroup('seedEntry.seedCultivar.name')
    ->filters([
        SelectFilter::make('cultivar')
            ->relationship('seedEntry.seedCultivar', 'name'),
        SelectFilter::make('supplier')
            ->relationship('seedEntry.supplier', 'name'),
        SelectFilter::make('stock_status')
            ->options([
                '1' => 'In Stock',
                '0' => 'Out of Stock',
            ])
            ->attribute('is_in_stock'),
    ]);
}
```

## Data Import Logic

```php
class SeedScrapeImporter
{
    public function import(string $jsonFilePath, SeedScrapeUpload $scrapeUpload): void
    {
        try {
            $jsonData = json_decode(file_get_contents($jsonFilePath), true);
            
            if (!isset($jsonData['data']) || !is_array($jsonData['data'])) {
                throw new \Exception("Invalid JSON format: 'data' array not found");
            }
            
            // Extract supplier information from the data
            $supplierName = $jsonData['source_site'] ?? 'Unknown Supplier';
            $supplier = Supplier::firstOrCreate(['name' => $supplierName]);
            
            // Process each product
            foreach ($jsonData['data'] as $productData) {
                $this->processProduct($productData, $supplier, $jsonData['timestamp'] ?? now());
            }
            
            // Update the scrape upload record
            $scrapeUpload->update([
                'status' => 'completed',
                'processed_at' => now(),
                'notes' => 'Successfully processed ' . count($jsonData['data']) . ' products.'
            ]);
            
        } catch (\Exception $e) {
            // Handle any exceptions
            $scrapeUpload->update([
                'status' => 'error',
                'processed_at' => now(),
                'notes' => 'Error: ' . $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    protected function processProduct(array $productData, Supplier $supplier, string $timestamp): void
    {
        // Extract cultivar name from the title
        $cultivarName = $productData['cultivar'] ?? 'Unknown Cultivar';
        $seedCultivar = SeedCultivar::firstOrCreate(['name' => $cultivarName]);
        
        // Find or create the seed entry
        $seedEntry = SeedEntry::firstOrCreate(
            [
                'supplier_id' => $supplier->id,
                'supplier_product_url' => $productData['url'] ?? '',
            ],
            [
                'seed_cultivar_id' => $seedCultivar->id,
                'supplier_product_title' => $productData['title'] ?? 'Unknown Product',
                'image_url' => $productData['image_url'] ?? null,
                'description' => $productData['description'] ?? null,
                'tags' => $productData['tags'] ?? [],
            ]
        );
        
        // Process each variant
        if (isset($productData['variants']) && is_array($productData['variants'])) {
            foreach ($productData['variants'] as $variantData) {
                $this->processVariant($variantData, $seedEntry, $timestamp);
            }
        }
    }
    
    protected function processVariant(array $variantData, SeedEntry $seedEntry, string $timestamp): void
    {
        $sizeDescription = $variantData['variant_title'] ?? 'Default';
        
        // Find or create the seed variation
        $variation = SeedVariation::firstOrCreate(
            [
                'seed_entry_id' => $seedEntry->id,
                'size_description' => $sizeDescription,
            ],
            [
                'sku' => $variantData['sku'] ?? null,
                'weight_kg' => $variantData['weight_kg'] ?? null,
                'original_weight_value' => $variantData['original_weight_value'] ?? null,
                'original_weight_unit' => $variantData['original_weight_unit'] ?? null,
                'current_price' => $variantData['price'] ?? 0,
                'is_in_stock' => $variantData['is_variant_in_stock'] ?? false,
                'last_checked_at' => now(),
            ]
        );
        
        // Update the variation with the latest data
        $priceChanged = $variation->current_price != ($variantData['price'] ?? 0);
        $stockChanged = $variation->is_in_stock != ($variantData['is_variant_in_stock'] ?? false);
        
        $variation->update([
            'current_price' => $variantData['price'] ?? $variation->current_price,
            'is_in_stock' => $variantData['is_variant_in_stock'] ?? $variation->is_in_stock,
            'last_checked_at' => now(),
        ]);
        
        // Create a price history record only if price or stock status changed
        if ($priceChanged || $stockChanged) {
            SeedPriceHistory::create([
                'seed_variation_id' => $variation->id,
                'price' => $variantData['price'] ?? $variation->current_price,
                'is_in_stock' => $variantData['is_variant_in_stock'] ?? $variation->is_in_stock,
                'scraped_at' => Carbon::parse($timestamp),
            ]);
        }
        
        // Check if we need to create or update a consumable entry
        $this->syncWithConsumableInventory($variation, $seedEntry);
    }
    
    protected function syncWithConsumableInventory(SeedVariation $variation, SeedEntry $seedEntry): void
    {
        // Skip if already linked to a consumable
        if ($variation->consumable_id) {
            return;
        }
        
        // Try to find an existing consumable with matching details
        $consumable = Consumable::where('type', 'seed')
            ->where('name', 'LIKE', '%' . $seedEntry->seedCultivar->name . '%')
            ->where('sku', $variation->sku)
            ->first();
            
        if (!$consumable) {
            // Create a new consumable entry if one doesn't exist
            $consumable = Consumable::create([
                'type' => 'seed',
                'name' => $seedEntry->seedCultivar->name . ' - ' . $variation->size_description,
                'sku' => $variation->sku,
                'quantity' => 0, // Start with 0 since we don't know the actual inventory
                'restock_level' => 1, // Default restock level
                'weight_kg' => $variation->weight_kg,
                'supplier_id' => $seedEntry->supplier_id,
                // Add other required fields based on your Consumable model
            ]);
        }
        
        // Link the variation to the consumable
        $variation->update([
            'consumable_id' => $consumable->id
        ]);
    }
}
```

## Filament Custom Pages

### SeedReorderAdvisorPage
```php
class SeedReorderAdvisorPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string $view = 'filament.pages.seed-reorder-advisor';
    protected static ?string $title = 'Seed Reorder Advisor';
    
    public function getCultivarOptions()
    {
        return SeedCultivar::orderBy('name')->pluck('name', 'id');
    }
    
    public function getData(int $cultivarId = null)
    {
        $query = SeedVariation::query()
            ->with(['seedEntry.supplier', 'seedEntry.seedCultivar', 'consumable'])
            ->where('is_in_stock', true);
            
        if ($cultivarId) {
            $query->whereHas('seedEntry', function ($q) use ($cultivarId) {
                $q->where('seed_cultivar_id', $cultivarId);
            });
        }
        
        return $query
            ->orderByRaw('current_price / NULLIF(weight_kg, 0) ASC')
            ->get();
    }
}
```

### SeedPriceTrendsPage
```php
class SeedPriceTrendsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.seed-price-trends';
    protected static ?string $title = 'Seed Price Trends';
    
    public function getCultivarOptions()
    {
        return SeedCultivar::orderBy('name')->pluck('name', 'id');
    }
    
    public function getPriceData(array $cultivarIds = [], int $monthsAgo = 12)
    {
        $startDate = now()->subMonths($monthsAgo);
        
        return SeedPriceHistory::query()
            ->select(
                DB::raw('DATE_FORMAT(scraped_at, "%Y-%m") as month'),
                DB::raw('AVG(price / NULLIF(seed_variations.weight_kg, 0)) as avg_price_per_kg'),
                'seed_entries.seed_cultivar_id'
            )
            ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
            ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
            ->whereIn('seed_entries.seed_cultivar_id', $cultivarIds)
            ->where('scraped_at', '>=', $startDate)
            ->groupBy('month', 'seed_entries.seed_cultivar_id')
            ->orderBy('month')
            ->get();
    }
}
```

## Action Buttons for Import

```php
public static function getHeaderActions(): array
{
    return [
        FileUpload::make('jsonFile')
            ->label('Upload Scrape JSON')
            ->disk('local')
            ->directory('seed-scrape-uploads')
            ->acceptedFileTypes(['application/json'])
            ->maxSize(10240) // 10MB
            ->action(function ($livewire, TemporaryUploadedFile $file) {
                // Create a record of the upload
                $scrapeUpload = SeedScrapeUpload::create([
                    'original_filename' => $file->getClientOriginalName(),
                    'status' => 'pending',
                    'uploaded_at' => now(),
                ]);
                
                // Process the upload
                dispatch(new ProcessSeedScrapeUpload(
                    $scrapeUpload,
                    $file->getRealPath()
                ));
                
                Notification::make()
                    ->title('Upload received')
                    ->body('Your file is being processed.')
                    ->success()
                    ->send();
            }),
    ];
}
```

## Integration with Existing Consumables System

```php
// Add a relation manager to the ConsumableResource for seed variations
class SeedVariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'seedVariations';

    public static function getTitle(): string
    {
        return 'Seed Supplier Variations';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seedEntry.seedCultivar.name')
                    ->label('Cultivar')
                    ->sortable(),
                TextColumn::make('seedEntry.supplier.name')
                    ->label('Supplier')
                    ->sortable(),
                TextColumn::make('size_description')
                    ->sortable(),
                TextColumn::make('current_price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('price_per_kg')
                    ->money('USD')
                    ->getStateUsing(fn (SeedVariation $record): ?float => $record->price_per_kg),
                IconColumn::make('is_in_stock')
                    ->boolean(),
                TextColumn::make('last_checked_at')
                    ->dateTime(),
            ]);
    }
}
```

## Implementation Steps

1. **Set Up Database**
   - Create the necessary migrations
   - Run migrations to set up the database

2. **Create Models**
   - Implement all the models with relationships
   - Add computed properties and helper methods

3. **Create Filament Resources**
   - Implement the SeedCultivarResource
   - Implement the SeedVariationResource
   - Add action buttons for JSON imports

4. **Implement JSON Import Logic**
   - Create the SeedScrapeImporter class
   - Set up the required job for processing uploads asynchronously
   - Implement integration with the existing consumables system

5. **Create Dashboard and Reports**
   - Implement the SeedReorderAdvisorPage
   - Implement the SeedPriceTrendsPage
   - Create chart widgets for price trends

6. **Testing**
   - Test the import functionality with sample JSON data
   - Verify data integrity and historical price tracking
   - Test the reporting features

## JSON Example for Testing

You can use the provided JSON structure in `docs/json_examples/example_json` for testing the import functionality. The example includes the required fields for products, cultivars, and variants that will be processed by the import system. 