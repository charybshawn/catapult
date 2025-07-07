# Configuration Guide

This document describes the configuration system for the Catapult application. All hardcoded values have been extracted to configuration files for easy customization.

## Configuration Files

The following configuration files have been created:

### 1. `config/inventory.php`
Manages inventory-related settings including:
- Low stock threshold (default: 15%)
- FIFO system settings
- Inventory alerts and notifications
- Depletion checking

### 2. `config/tasks.php`
Controls task system behavior:
- Memory limits for bulk operations (default: 100MB)
- Batch processing sizes (default: 100 items)
- Task retry settings
- Scheduling configuration
- Task type-specific settings

### 3. `config/crops.php`
Defines crop management settings:
- Default stage durations (germination: 2 days, blackout: 3 days, light: 7 days)
- Lifecycle settings and auto-advancement
- Alert configurations
- Batch processing settings
- Watering suspension settings
- Validation rules

### 4. `config/backup.php`
Configures database backup system:
- Storage paths and disk settings
- Size limits and warnings (warning at 100MB)
- Retention policies
- Processing options
- Scheduling configuration

### 5. `config/harvest.php`
Manages harvest calculation settings:
- Historical data analysis (6 months default)
- Yield calculation thresholds
- Quality tracking
- Planning buffer percentages
- Notification settings

## Environment Variables

All configuration values can be overridden using environment variables. Copy the provided `.env.example.config` file contents to your `.env` file and adjust values as needed.

### Key Environment Variables:

```env
# Inventory
LOW_STOCK_THRESHOLD=15.0        # Percentage threshold for low stock alerts
FIFO_STRICT_ENFORCEMENT=true    # Enforce FIFO inventory consumption

# Tasks
TASK_MEMORY_LIMIT=100           # MB limit for task processing
TASK_BATCH_SIZE=100             # Items processed per batch

# Crops
CROP_GERMINATION_DAYS=2         # Default germination duration
CROP_BLACKOUT_DAYS=3            # Default blackout duration
CROP_LIGHT_DAYS=7               # Default light duration

# Backup
BACKUP_WARNING_SIZE_MB=100      # Warning threshold for backup size
BACKUP_KEEP_COUNT=10            # Number of backups to retain

# Harvest
HARVEST_THRESHOLD_OVER=15.0     # % over expected for "exceeds" alert
HARVEST_DEFAULT_BUFFER=10.0     # Default planning buffer percentage
```

## Usage in Code

Configuration values are accessed using Laravel's `config()` helper:

```php
// Get a configuration value with default
$threshold = config('inventory.low_stock_threshold', 15.0);

// Get nested configuration
$batchSize = config('tasks.batch_size', 100);

// Check boolean configuration
if (config('crops.lifecycle.auto_advance', true)) {
    // Auto-advance enabled
}
```

## Updating Configuration

1. **Environment Variables**: For temporary or environment-specific changes, update your `.env` file
2. **Configuration Files**: For permanent changes across all environments, update the config files directly
3. **Cache**: After updating configuration files, clear the config cache:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

## Best Practices

1. **Never hardcode values** - Always use configuration
2. **Provide sensible defaults** - All config values should have reasonable defaults
3. **Document changes** - Update this guide when adding new configuration
4. **Use appropriate types** - Cast values properly (int, float, bool)
5. **Group related settings** - Keep configuration organized by feature

## Adding New Configuration

To add new configuration values:

1. Add the value to the appropriate config file (or create a new one)
2. Add the environment variable to `.env.example.config`
3. Update this documentation
4. Use `config('file.key', default)` in your code
5. Clear config cache after deployment