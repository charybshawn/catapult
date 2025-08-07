# Enable Live Debugging for Agents

## Quick Setup Commands

### 1. Enable Query Logging (Immediate)
```bash
# Add to .env for detailed query logging
DB_LOG_QUERIES=true

# Enable debug mode for more verbose logging  
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

### 2. Laravel Telescope Installation (Recommended)
```bash
# Install Telescope for comprehensive debugging
composer require laravel/telescope --dev

# Publish and migrate
php artisan telescope:install
php artisan migrate

# Enable in local environment only
php artisan telescope:publish
```

### 3. Laravel Debugbar (Alternative)
```bash
# Lighter weight option for query debugging
composer require barryvdh/laravel-debugbar --dev

# Publish config (optional)
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

## Agent Integration Benefits

### With Telescope:
- Agents can access `/telescope` to see real-time data
- Query analysis with exact SQL and performance metrics
- Request timeline showing what happens during row hiding
- Exception tracking for debugging errors

### With Query Logging:
- Agents can tail logs: `tail -f storage/logs/laravel.log`
- See exact SQL queries being executed
- Track performance bottlenecks
- Monitor session state changes

### With Debugbar:
- Agents can analyze HTTP responses with query data
- See N+1 query issues immediately
- Monitor memory usage during operations
- Track view rendering performance

## Usage for Row Hiding Debug:
1. Enable query logging
2. Hide a row while monitoring logs
3. Agent can see exact SQL queries executed
4. Compare before/after query results
5. Identify ordering/filtering issues in real-time