# Consistent SlideOver System

This system provides a consistent, reusable way to implement slideOver modals across all Filament resources in the application.

## Quick Start

1. **Add the trait to your resource:**
```php
use App\Filament\Traits\HasConsistentSlideOvers;

class YourResource extends Resource
{
    use HasConsistentSlideOvers;
    
    // ... your resource code
}
```

2. **Use predefined configurations:**
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->actions(static::getStandardTableActions(SlideOverConfigurations::user()))
        ->headerActions(static::getStandardHeaderActions(SlideOverConfigurations::user()));
}
```

## Available Configurations

- `SlideOverConfigurations::user()` - For user/customer resources
- `SlideOverConfigurations::product()` - For product resources  
- `SlideOverConfigurations::order()` - For order resources
- `SlideOverConfigurations::supplier()` - For supplier resources
- `SlideOverConfigurations::consumable()` - For inventory resources
- `SlideOverConfigurations::recipe()` - For recipe resources

## Customization

### Custom Configuration
```php
->actions(static::getStandardTableActions([
    'viewConfig' => [
        'tooltip' => 'View details',
        'heading' => 'Custom View Title',
        'description' => fn($record) => 'Description for ' . $record->name,
        'icon' => 'heroicon-o-eye',
        'footerActions' => [
            Tables\Actions\Action::make('custom_action')
                ->label('Custom Action')
                ->icon('heroicon-o-star')
                ->action(fn($record) => null),
        ],
    ],
    'editConfig' => [...],
    'createConfig' => [...],
]))
```

### Disable Actions
```php
->actions(static::getStandardTableActions([
    'view' => false,    // No view action
    'delete' => false,  // No delete action
]))
```

### Individual Actions
```php
// Create individual actions
$viewAction = static::makeViewAction(['heading' => 'Custom View']);
$editAction = static::makeEditAction(['heading' => 'Custom Edit']);
$createAction = static::makeCreateAction(['label' => 'Create New']);
```

## Features

- **Consistent 3xl modal width** across all slideOvers
- **Custom icons and descriptions** for each action type
- **Footer actions** for quick access to related functionality
- **Responsive design** with proper scrolling
- **Easy customization** while maintaining consistency

## Benefits

1. **Consistency** - All slideOvers look and behave the same way
2. **Reusability** - One line of code to add standard actions
3. **Customizable** - Easy to override specific parts when needed
4. **Maintainable** - Changes to the system affect all resources
5. **Developer Friendly** - Clear patterns and examples

## Examples

See `app/Filament/Examples/ExampleResourceWithSlideOver.php` for complete usage examples.