# Filament Resource Architecture Guide

## 🎯 Core Philosophy: Work WITH Filament, Not Against It

This guide establishes architectural standards for organizing Filament resources in a clean, maintainable way. **The key principle is to leverage Filament's built-in extensibility patterns rather than fighting them or creating parallel systems.**

### ⚠️ Critical Understanding
We are **NOT** creating new frameworks or replacing Filament functionality. We are **organizing and extending Filament's existing patterns** to avoid code sprawl while staying within Filament's ecosystem.

## 🚫 What NOT to Do (Anti-Patterns)

### ❌ Fighting Filament
```php
// DON'T create Laravel observers when Filament has Actions
class ProductObserver {
    public function created(Product $product) { /* custom logic */ }
}

// DON'T create custom services when Filament has Action patterns  
class ProductService {
    public function createProduct($data) { /* custom logic */ }
}

// DON'T create custom blade templates when Filament has components
// resources/views/custom-product-form.blade.php

// DON'T reinvent form building when Filament has schema
public function buildCustomForm() {
    return view('custom-form-builder');
}
```

### ✅ Working WITH Filament
```php
// DO use Filament's Action pattern
class CreateProductAction {
    public function execute(array $data): Product { /* business logic */ }
}

// DO use Filament's form schema, just organize it better
class ProductForm {
    public static function schema(): array {
        return [ /* Filament form components */ ];
    }
}

// DO use Filament's table columns, just organize them
class ProductTable {
    public static function columns(): array {
        return [ /* Filament table columns */ ];
    }
}

// DO use Filament's page hooks and lifecycle methods
class CreateProduct extends CreateRecord {
    protected function handleRecordCreation(array $data): Model {
        return app(CreateProductAction::class)->execute($data);
    }
}
```

## 🏗️ Architecture Overview

Our approach **extends Filament classes** and **organizes Filament patterns** without replacing them:

- **Main Resource** → Extends `Filament\Resources\Resource`
- **Form Classes** → Return Filament form schema arrays  
- **Table Classes** → Return Filament table component arrays
- **Action Classes** → Handle business logic, called from Filament lifecycle hooks
- **Page Classes** → Extend Filament page classes (`CreateRecord`, `EditRecord`, etc.)

## 📁 File Structure (Extending Filament)

```
app/Filament/Resources/
├── XxxResource.php                    # Extends Filament\Resources\Resource
├── XxxResource/
│   ├── Forms/
│   │   └── XxxForm.php               # Returns Filament form schema
│   ├── Tables/
│   │   └── XxxTable.php              # Returns Filament table components
│   ├── Actions/
│   │   └── XxxAction.php             # Custom Filament actions (minimal logic)
│   └── Pages/
│       ├── ListXxx.php               # Extends Filament\Resources\Pages\ListRecords
│       ├── CreateXxx.php             # Extends Filament\Resources\Pages\CreateRecord
│       ├── EditXxx.php               # Extends Filament\Resources\Pages\EditRecord
│       └── ViewXxx.php               # Extends Filament\Resources\Pages\ViewRecord

app/Actions/Xxx/                      # Business logic classes (not Filament classes)
├── CreateXxx.php                     # Pure business logic
├── UpdateXxx.php                     # Pure business logic
└── [SpecificOperations].php          # Domain-specific operations

resources/views/filament/             # Blade views for complex HTML output
├── actions/
│   └── xxx-debug.blade.php           # HTML templates for action outputs
├── components/
│   └── xxx-badge.blade.php           # Reusable view components
└── [other-view-directories]/
```

## 🔧 Implementation Templates

### Main Resource (Extends Filament Resource)

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\XxxResource\Forms\XxxForm;
use App\Filament\Resources\XxxResource\Pages;
use App\Filament\Resources\XxxResource\Tables\XxxTable;
use App\Models\Xxx;
use Filament\Forms\Form;
use Filament\Resources\Resource;  // ← Extending Filament
use Filament\Tables\Table;

class XxxResource extends Resource  // ← This IS a Filament Resource
{
    protected static ?string $model = Xxx::class;
    
    // Standard Filament navigation properties
    protected static ?string $navigationIcon = 'heroicon-o-xxx';
    protected static ?string $navigationLabel = 'Xxxs';
    protected static ?string $navigationGroup = 'Group Name';
    
    // Delegate to organized components, but return Filament objects
    public static function form(Form $form): Form  // ← Filament Form object
    {
        return $form->schema(XxxForm::schema());  // ← Returns Filament schema
    }

    public static function table(Table $table): Table  // ← Filament Table object
    {
        return $table
            ->columns(XxxTable::columns())      // ← Filament columns
            ->filters(XxxTable::filters())      // ← Filament filters  
            ->actions(XxxTable::actions())      // ← Filament actions
            ->bulkActions(XxxTable::bulkActions())  // ← Filament bulk actions
            ->defaultSort('created_at', 'desc');    // ← Filament method
    }

    // Standard Filament pages
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListXxx::route('/'),      // ← Filament routing
            'create' => Pages\CreateXxx::route('/create'),
            'view' => Pages\ViewXxx::route('/{record}'),
            'edit' => Pages\EditXxx::route('/{record}/edit'),
        ];
    }
}
```

### Form Component (Returns Filament Schema)

```php
<?php

namespace App\Filament\Resources\XxxResource\Forms;

use Filament\Forms;  // ← Using Filament form components
use Filament\Forms\Get;
use Filament\Forms\Set;

class XxxForm
{
    /**
     * Returns Filament form schema - NOT a custom form system
     */
    public static function schema(): array  // ← Returns Filament schema array
    {
        return [
            // These are ALL Filament components, just organized
            Forms\Components\Section::make('Basic Information')  // ← Filament Section
                ->schema([
                    Forms\Components\TextInput::make('name')      // ← Filament TextInput
                        ->label('Name')
                        ->required()
                        ->reactive(),
                        
                    Forms\Components\Select::make('status')       // ← Filament Select  
                        ->label('Status')
                        ->options(static::getStatusOptions())
                        ->required(),
                ])
                ->columns(2),
            
            // Conditional sections using Filament's reactive system    
            Forms\Components\Section::make('Advanced Settings')
                ->schema(static::getAdvancedFields())             // ← More Filament components
                ->visible(fn (Get $get) => $get('status') === 'active')  // ← Filament reactive
                ->collapsed(),
        ];
    }
    
    protected static function getAdvancedFields(): array
    {
        return [
            Forms\Components\Textarea::make('notes')              // ← Filament Textarea
                ->label('Notes')
                ->rows(3),
        ];
    }
    
    protected static function getStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }
}
```

### Table Component (Returns Filament Components)

```php
<?php

namespace App\Filament\Resources\XxxResource\Tables;

use Filament\Tables;  // ← Using Filament table components
use Illuminate\Database\Eloquent\Builder;

class XxxTable
{
    /**
     * Returns Filament table columns - NOT a custom table system
     */
    public static function columns(): array  // ← Returns Filament columns array
    {
        return [
            // These are ALL Filament columns, just organized
            Tables\Columns\TextColumn::make('name')               // ← Filament TextColumn
                ->label('Name')
                ->sortable()
                ->searchable(),
                
            Tables\Columns\BadgeColumn::make('status')            // ← Filament BadgeColumn
                ->label('Status')
                ->colors(static::getStatusColors()),              // ← Configuration method
                
            Tables\Columns\TextColumn::make('created_at')         // ← Filament TextColumn
                ->label('Created')
                ->dateTime()
                ->sortable(),
        ];
    }
    
    public static function filters(): array  // ← Returns Filament filters
    {
        return [
            Tables\Filters\SelectFilter::make('status')          // ← Filament SelectFilter
                ->options(static::getStatusOptions()),
                
            Tables\Filters\DateRangeFilter::make('created_at')   // ← Filament DateRangeFilter
                ->label('Created Date'),
        ];
    }
    
    public static function actions(): array  // ← Returns Filament actions
    {
        return [
            Tables\Actions\Action::make('customAction')          // ← Filament Action
                ->label('Custom Action')
                ->icon('heroicon-o-cog')
                ->action(function ($record) {
                    // Call business logic, but this is still a Filament action
                    app(\App\Actions\Xxx\CustomAction::class)->execute($record);
                }),
                
            Tables\Actions\ViewAction::make(),                   // ← Filament ViewAction
            Tables\Actions\EditAction::make(),                   // ← Filament EditAction
        ];
    }
    
    protected static function getStatusColors(): array
    {
        return [
            'active' => 'success',
            'inactive' => 'gray',
        ];
    }
}
```

### Page Classes (Extend Filament Pages)

```php
<?php

namespace App\Filament\Resources\XxxResource\Pages;

use App\Actions\Xxx\CreateXxx;
use App\Filament\Resources\XxxResource;
use Filament\Resources\Pages\CreateRecord;  // ← Extending Filament page
use Illuminate\Database\Eloquent\Model;

class CreateXxx extends CreateRecord  // ← This IS a Filament page
{
    protected static string $resource = XxxResource::class;
    
    /**
     * Uses Filament's handleRecordCreation hook - NOT replacing it
     */
    protected function handleRecordCreation(array $data): Model  // ← Filament lifecycle hook
    {
        // Business logic is delegated to Action, but we're still in Filament's system
        return app(CreateXxx::class)->execute($data);
    }
    
    /**
     * Uses Filament's redirect system
     */
    protected function getRedirectUrl(): string  // ← Filament method
    {
        return $this->getResource()::getUrl('index');  // ← Filament URL helper
    }
    
    /**
     * Uses Filament's notification system  
     */
    protected function getCreatedNotificationTitle(): ?string  // ← Filament method
    {
        return 'Record created successfully';
    }
}
```

### Business Logic Actions (Pure PHP, NOT Filament)

```php
<?php

namespace App\Actions\Xxx;

use App\Models\Xxx;
use Illuminate\Support\Facades\DB;

/**
 * This is NOT a Filament class - pure business logic
 * Called FROM Filament hooks, but independent of Filament
 */
class CreateXxx
{
    public function execute(array $data): Xxx
    {
        // Pure business logic - no Filament dependencies
        return DB::transaction(function () use ($data) {
            $xxx = Xxx::create($this->prepareData($data));
            $this->performPostCreationTasks($xxx);
            return $xxx->fresh();
        });
    }
    
    protected function prepareData(array $data): array
    {
        // Business data transformation
        return [
            'name' => $data['name'],
            'slug' => \Str::slug($data['name']),
            'status' => $data['status'] ?? 'active',
        ];
    }
    
    protected function performPostCreationTasks(Xxx $xxx): void
    {
        // Additional business operations
        // Event dispatching
        // Cache clearing
        // Email notifications
    }
}
```

## 🎯 Key Principles in Practice

### 1. **Extend, Don't Replace**
```php
// ✅ GOOD: Extending Filament's CreateRecord
class CreateProduct extends CreateRecord {
    protected function handleRecordCreation(array $data): Model {
        return app(CreateProductAction::class)->execute($data);
    }
}

// ❌ BAD: Creating parallel system
class CustomProductCreator {
    public function createForm() { /* custom form system */ }
    public function handleSubmission() { /* bypassing Filament */ }
}
```

### 2. **Organize, Don't Reinvent**
```php
// ✅ GOOD: Organizing Filament components
class ProductForm {
    public static function schema(): array {
        return [ /* Filament form components */ ];
    }
}

// ❌ BAD: Custom form builder
class CustomFormBuilder {
    public function buildForm() { /* custom HTML/Vue/React */ }
}
```

### 3. **Use Filament's Lifecycle**
```php
// ✅ GOOD: Using Filament's built-in hooks
protected function handleRecordCreation(array $data): Model
protected function handleRecordUpdate(Model $record, array $data): Model  
protected function beforeSave(): void
protected function afterSave(): void

// ❌ BAD: Custom event system
Event::listen('product.creating', ProductListener::class);
```

### 4. **Leverage Filament's Action System**
```php
// ✅ GOOD: Filament table action calling business logic
Tables\Actions\Action::make('approve')
    ->action(fn ($record) => app(ApproveAction::class)->execute($record))

// ❌ BAD: Custom observers
class ProductObserver {
    public function updating(Product $product) { /* custom logic */ }
}
```

## 🎨 HTML and Display Separation

**ALWAYS use Blade views for complex HTML output to maintain clean separation of concerns:**

### Why Separate HTML from Logic?
- **Maintainability**: HTML changes don't require touching PHP classes
- **Testability**: Business logic can be tested independently of presentation
- **Designer Friendly**: Frontend developers can work on views without PHP knowledge
- **Code Clarity**: Actions focus on orchestration, views focus on presentation

### Implementation Pattern

#### ✅ GOOD: Action delegates to Blade view
```php
<?php

namespace App\Filament\Resources\ProductResource\Actions;

use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ProductDebugAction
{
    public static function make(): Action
    {
        return Action::make('debug')
            ->label('Debug')
            ->icon('heroicon-o-code-bracket')
            ->action(function ($record) {
                // Minimal logic - just data preparation and view delegation
                $htmlOutput = view('filament.actions.product-debug', [
                    'record' => $record,
                    'analytics' => app(ProductAnalytics::class)->gather($record),
                    'relationships' => $record->load(['orders', 'inventory']),
                ])->render();

                Notification::make()
                    ->title('Product Debug Information')
                    ->body($htmlOutput)
                    ->persistent()
                    ->send();
            });
    }
}
```

#### ✅ GOOD: Dedicated Blade view
```blade
{{-- resources/views/filament/actions/product-debug.blade.php --}}
<div class="space-y-4">
    <div class="mb-4">
        <h3 class="text-lg font-medium mb-2">Product Information</h3>
        <div class="overflow-auto max-h-48 space-y-1">
            <div class="flex">
                <span class="font-medium w-32">Name:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->name }}</span>
            </div>
            <div class="flex">
                <span class="font-medium w-32">SKU:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->sku }}</span>
            </div>
        </div>
    </div>

    @if($analytics)
        <div class="mb-4">
            <h3 class="text-lg font-medium mb-2">Analytics</h3>
            <div class="space-y-2">
                @foreach($analytics as $metric => $value)
                    <div class="flex">
                        <span class="font-medium w-40 text-sm">{{ $metric }}:</span>
                        <span class="text-gray-600 dark:text-gray-400 text-sm">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
```

#### ❌ BAD: HTML built in action class
```php
class ProductDebugAction
{
    public static function make(): Action
    {
        return Action::make('debug')
            ->action(function ($record) {
                // BAD: Building HTML strings in PHP
                $html = '<div class="space-y-4">';
                $html .= '<div class="mb-4">';
                $html .= '<h3 class="text-lg font-medium mb-2">Product Information</h3>';
                $html .= '<div class="overflow-auto max-h-48 space-y-1">';
                foreach ($data as $key => $value) {
                    $html .= '<div class="flex">';
                    $html .= '<span class="font-medium w-32">' . $key . ':</span>';
                    $html .= '<span class="text-gray-600">' . $value . '</span>';
                    $html .= '</div>';
                }
                $html .= '</div></div></div>';
                
                // This is unmaintainable and hard to read
                Notification::make()->body($html)->send();
            });
    }
}
```

### View Organization Guidelines

#### Directory Structure
```
resources/views/filament/
├── actions/                    # Action-specific views
│   ├── product-debug.blade.php
│   ├── order-summary.blade.php
│   └── batch-operations.blade.php
├── components/                 # Reusable components
│   ├── status-badge.blade.php
│   ├── timeline.blade.php
│   └── data-table.blade.php
├── widgets/                    # Widget views
│   └── dashboard-stats.blade.php
└── emails/                     # Email templates
    └── order-confirmation.blade.php
```

#### Component Reusability
```blade
{{-- resources/views/filament/components/status-badge.blade.php --}}
@props(['status', 'color' => 'gray'])

<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
    @if($color === 'success') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200
    @elseif($color === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200
    @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200
    @endif">
    {{ $status }}
</span>
```

```blade
{{-- Usage in action views --}}
<x-filament.components.status-badge :status="$record->status" :color="$record->status_color" />
```

### Benefits of View Separation

1. **Maintainability**: HTML changes don't require PHP expertise
2. **Testability**: Actions can be unit tested without HTML concerns
3. **Performance**: Views can be cached independently
4. **Collaboration**: Designers can work on views while developers work on logic
5. **Consistency**: Shared view components ensure UI consistency

## ⚡ Benefits of Working WITH Filament

### 1. **Automatic Updates**
When Filament updates, your code automatically benefits from improvements.

### 2. **Consistent UI/UX**
Users get consistent Filament experience across all resources.

### 3. **Full Feature Access**
You get all of Filament's features: theming, authorization, caching, etc.

### 4. **Community Support**
Solutions and plugins from the Filament community work with your code.

### 5. **Documentation Alignment**
Filament's official documentation applies to your organized code.

## 🚨 Warning Signs You're Fighting Filament

- Creating custom blade templates for forms/tables
- Bypassing Filament's form schema system
- Creating Laravel observers instead of using Filament actions
- Building custom services that duplicate Filament functionality
- Creating custom routing that bypasses Filament's resource routing
- Implementing custom authorization outside Filament's policy system
- **Building HTML strings in PHP classes instead of using Blade views**
- Creating custom notification systems instead of using Filament's notifications

## 📊 Quality Metrics

### File Size Guidelines
- **Main Resource:** Max 150 lines (mostly delegation)
- **Form/Table Classes:** Max 300 lines (organized Filament components)
- **Action Classes:** Max 100 lines (pure business logic)
- **Page Classes:** Max 200 lines (Filament lifecycle hooks)

### Architecture Validation
- [ ] All form fields use `Filament\Forms\Components\*`
- [ ] All table columns use `Filament\Tables\Columns\*`  
- [ ] All pages extend `Filament\Resources\Pages\*`
- [ ] Business logic is in separate Action classes
- [ ] No custom blade templates for forms/tables
- [ ] No Laravel observers for Filament-managed operations
- [ ] **Complex HTML output uses Blade views, not PHP string building**
- [ ] Action classes delegate to views for presentation logic
- [ ] Views are organized in logical directory structure

## 🔄 Refactoring Anti-Pattern Code

### Step 1: Identify Fighting-Filament Code
Look for:
- Custom form builders
- Laravel observers doing UI-related work
- Custom services duplicating Filament features
- Blade templates for forms/tables
- Custom routing bypassing Filament

### Step 2: Extract to Filament Patterns
```php
// Before: Custom observer
class ProductObserver {
    public function created(Product $product) {
        $this->sendNotification($product);
        $this->updateInventory($product);
    }
}

// After: Filament page hook + Action
class CreateProduct extends CreateRecord {
    protected function handleRecordCreation(array $data): Model {
        return app(CreateProductAction::class)->execute($data);
    }
}

class CreateProductAction {
    public function execute(array $data): Product {
        $product = Product::create($data);
        $this->sendNotification($product);
        $this->updateInventory($product);
        return $product;
    }
}
```

### Step 3: Organize Filament Components
```php
// Before: Massive resource file
class ProductResource extends Resource {
    public static function form(Form $form): Form {
        return $form->schema([
            // 100+ lines of form components
        ]);
    }
}

// After: Organized delegation
class ProductResource extends Resource {
    public static function form(Form $form): Form {
        return $form->schema(ProductForm::schema());
    }
}

class ProductForm {
    public static function schema(): array {
        return [ /* organized Filament components */ ];
    }
}
```

## 🎉 Success Example: CropResource

The CropResource demonstrates perfect "work WITH Filament" architecture:

- **CropResource.php** - Clean Filament resource (110 lines)
- **CropForm.php** - Organized Filament form schema
- **CropTable.php** - Organized Filament table components  
- **AdvanceFromSoaking.php** - Pure business logic Action
- **Page classes** - Extend Filament pages, use lifecycle hooks

Everything uses Filament's built-in patterns, just organized cleanly.

## 🎯 Remember

**We're not building a replacement for Filament. We're organizing Filament better.**

Every class should either:
1. **Extend a Filament class** (Resources, Pages, etc.)
2. **Return Filament components** (Forms, Tables)  
3. **Be pure business logic** (Actions)

This keeps us in Filament's ecosystem while preventing code sprawl.