# Suggested Commands for Catapult Project

## Development Commands
- `composer dev` - Start full development environment (server, queue, logs, vite)
- `php artisan serve` - Start Laravel development server
- `php artisan queue:listen --tries=1` - Start queue worker
- `php artisan pail --timeout=0` - Real-time log monitoring
- `npm run dev` - Start Vite development server

## Testing Commands
- `php artisan test` - Run PHPUnit tests
- `php artisan test --filter=<TestName>` - Run specific test
- `vendor/bin/phpunit` - Alternative test runner

## Code Quality
- `vendor/bin/pint` - Laravel Pint code formatter
- `vendor/bin/larastan` - Static analysis with Larastan

## Database Commands
- `php artisan migrate` - Run migrations
- `php artisan migrate:fresh --seed` - Fresh migration with seeders
- `php artisan db:seed` - Run seeders only

## Filament Commands
- `php artisan filament:upgrade` - Upgrade Filament
- `php artisan make:filament-resource` - Create new resource
- `php artisan make:filament-page` - Create new page

## Activity Log Commands
- `php artisan activity:test` - Test activity logging functionality
- `php artisan activity:purge` - Clean old activity logs
- `php artisan activity:maintenance` - Maintain activity log tables

## Cache Commands
- `php artisan config:cache` - Cache configuration
- `php artisan route:cache` - Cache routes
- `php artisan view:cache` - Cache views
- `php artisan optimize` - Run all optimizations

## Git Workflow
- Standard Git commands work normally
- Main branch: `main`
- Development branch: `develop`