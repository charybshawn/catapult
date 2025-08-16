# Coding Standards and Conventions

## PHP Standards
- **PSR-12** coding standard
- **Laravel conventions** for naming and structure
- **PHP 8.2+** features encouraged (typed properties, match expressions, etc.)

## Naming Conventions
- **Models**: PascalCase singular (User, TimeCard, CropPlan)
- **Controllers**: PascalCase with Controller suffix
- **Methods**: camelCase
- **Variables**: camelCase
- **Constants**: SCREAMING_SNAKE_CASE
- **Database tables**: snake_case plural
- **Database columns**: snake_case

## File Organization
- **Models**: `app/Models/`
- **Filament Resources**: `app/Filament/Resources/`
- **Services**: `app/Services/`
- **Traits**: `app/Traits/`
- **Observers**: `app/Observers/`

## Filament Resource Structure
```
app/Filament/Resources/
├── XxxResource.php (MAX 150 lines)
├── XxxResource/
│   ├── Forms/XxxForm.php
│   ├── Tables/XxxTable.php
│   ├── Actions/XxxAction.php
│   └── Pages/
```

## Documentation
- **PHPDoc blocks** required for all public methods
- **Type hints** required for all parameters and return types
- **Model relationships** should be documented
- **Complex business logic** should have comments

## Code Quality Rules
- **No code sprawl** - finish one approach before trying another
- **Extend existing code** rather than creating new files
- **Use Laravel/Filament patterns** - don't reinvent the wheel
- **Keep methods focused** - single responsibility principle