# Task Completion Checklist

## Before Completing Any Task

### Code Quality
- [ ] Code follows PSR-12 and Laravel conventions
- [ ] PHPDoc blocks added for new public methods
- [ ] Type hints used for parameters and return types
- [ ] No unused imports or variables

### Testing
- [ ] Run `php artisan test` to ensure no regressions
- [ ] Test the specific functionality you modified
- [ ] Check that existing features still work

### Code Review
- [ ] Review changes for potential side effects
- [ ] Ensure no code sprawl was introduced
- [ ] Verify all file changes are necessary
- [ ] Check that naming follows conventions

### Laravel/Filament Specific
- [ ] Filament resources follow the required structure
- [ ] Models use proper relationships and scopes
- [ ] Activity logging works correctly
- [ ] No direct database queries in views/controllers

### Final Steps
- [ ] Run `vendor/bin/pint` for code formatting
- [ ] Clear caches if configuration changed: `php artisan optimize:clear`
- [ ] Update documentation if API changed
- [ ] Consider if migration needed for schema changes

## Git Workflow
- [ ] Commit messages are descriptive
- [ ] Changes are atomic and focused
- [ ] Branch from `develop` for features
- [ ] Merge back to `develop` before `main`