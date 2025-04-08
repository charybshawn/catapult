# Cursor Rules for Catapult v2

This directory contains custom rules for the Cursor IDE to help maintain code quality and consistency in the Catapult v2 project.

## Available Rules

### Filament Icon Format

The `filament-icons.json` rule ensures proper icon formatting in Filament applications. Filament uses a specific format for Heroicons, requiring prefixes based on the icon style.

#### Supported Prefixes:

- `heroicon-o-`: Outline style (default for navigation)
- `heroicon-s-`: Solid style
- `heroicon-m-`: Mini style
- `heroicon-c-`: Micro style

#### Common Icon Issues:

- `heroicon-o-box` doesn't exist. Use `heroicon-o-archive-box` instead.
- Always verify that the icon name exists in the Heroicons set.

#### Important: Blade UI Icons vs. Filament Icons

There's an important difference in how icons are referenced:

1. In Filament's PHP code (like navigation icons or action icons), use:
   - `heroicon-o-home`
   - `heroicon-s-home`

2. In Blade templates with Blade UI Kit components, use:
   - `<x-heroicon-o-home />`
   - `<x-heroicon-s-home />`

Note that Blade UI Kit separates the style prefix (`o` or `s`) and the icon name, while Filament combines them with hyphens.

#### Examples

Correct usage:
```php
// In PHP (Filament resources, pages, etc.)
protected static ?string $navigationIcon = 'heroicon-o-home';

Forms\Components\Actions\Action::make('approve')
    ->icon('heroicon-o-check-circle')
    ->color('success');

// In Blade templates
<x-heroicon-o-home class="w-6 h-6" />
<x-heroicon-s-check-circle class="w-4 h-4" />
```

Incorrect usage:
```php
// In PHP (wrong format)
protected static ?string $navigationIcon = 'home';
protected static ?string $navigationIcon = 'o-home';
// Non-existent icon
protected static ?string $navigationIcon = 'heroicon-o-box';

Forms\Components\Actions\Action::make('approve')
    ->icon('check-circle')
    ->color('success');

// In Blade templates (wrong format)
<x-icon::heroicon name="o-home" />
```

## How Cursor Rules Work

Cursor rules help identify potential issues in your codebase as you write code. They're based on pattern matching and provide helpful feedback when a rule is violated.

To get the most out of these rules:

1. Make sure your Cursor settings are configured to use custom rules
2. Review rule violations when they appear in the editor
3. Follow the suggested fixes to maintain code consistency

## References

- [Filament Icons Documentation](https://filamentphp.com/docs/3.x/support/icons)
- [Heroicons](https://heroicons.com/)
- [Blade UI Kit Icons](https://blade-ui-kit.com/blade-icons)

## Adding New Rules

To add a new rule:

1. Create a new JSON file in the `.cursor/rules` directory
2. Follow the rule format shown in existing rules
3. Define meaningful patterns and helpful error messages
4. Add examples of correct and incorrect usage
5. Update this README to document the new rule 