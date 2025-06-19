# Database Import/Export Commands

This document describes the database import/export functionality available in the application.

## Available Commands

### 1. List Tables - `db:tables`

List all database tables with optional filtering and record counts.

```bash
# List all tables
php artisan db:tables

# Filter tables by name
php artisan db:tables --filter=product

# Include record counts
php artisan db:tables --with-counts

# Combine options
php artisan db:tables --filter=order --with-counts
```

### 2. Export Table - `db:export`

Export any database table to JSON or CSV format.

```bash
# Basic export (defaults to JSON)
php artisan db:export [table_name]

# Export to CSV
php artisan db:export users --format=csv

# Export with custom output path
php artisan db:export products --output=/path/to/export.json

# Export limited records
php artisan db:export orders --limit=100

# Export with WHERE conditions
php artisan db:export orders --where=status:completed --where=customer_type:wholesale

# Exclude timestamps
php artisan db:export recipes --with-timestamps=false
```

#### Examples:

```bash
# Export all products to JSON
php artisan db:export products

# Export first 50 orders to CSV
php artisan db:export orders --format=csv --limit=50

# Export completed orders only
php artisan db:export orders --where=status:completed

# Export to specific location
php artisan db:export users --output=/tmp/users_backup.json
```

### 3. Import Table - `db:import`

Import data from JSON or CSV files into database tables.

```bash
# Basic import (auto-detects format from file extension)
php artisan db:import [table_name] [file_path]

# Validate data without importing
php artisan db:import products data.json --validate

# Truncate table before import (CAUTION: deletes existing data!)
php artisan db:import products data.json --truncate

# Import with column mapping
php artisan db:import users data.csv --map=email_address:email --map=full_name:name

# Import with custom chunk size
php artisan db:import orders large_file.json --chunk=500
```

#### Examples:

```bash
# Import products from JSON
php artisan db:import products /path/to/products.json

# Import with validation first
php artisan db:import recipes recipes.csv --validate

# Import and replace all data
php artisan db:import categories categories.json --truncate

# Import with column renaming
php artisan db:import users users.csv --map=user_email:email --map=user_name:name
```

## Export File Locations

By default, exports are saved to:
```
storage/app/exports/[table_name]_[timestamp].[format]
```

Example: `storage/app/exports/products_2025-06-18_143022.json`

## Data Formats

### JSON Format
- Exported as an array of objects
- Each object represents one database record
- Maintains data types (integers, floats, booleans, arrays)
- Human-readable with pretty printing

### CSV Format
- First row contains column headers
- Complex data types (arrays, objects) are JSON-encoded
- NULL values are represented as empty strings
- Boolean values are exported as "true" or "false"

## Common Use Cases

### 1. Backup Specific Tables
```bash
# Backup products table
php artisan db:export products --output=backups/products_$(date +%Y%m%d).json

# Backup only active users
php artisan db:export users --where=is_active:1 --output=backups/active_users.json
```

### 2. Migrate Data Between Environments
```bash
# Export from production
php artisan db:export master_seed_catalog --output=seed_data.json

# Import to development (after copying file)
php artisan db:import master_seed_catalog seed_data.json --truncate
```

### 3. Data Analysis
```bash
# Export orders for analysis
php artisan db:export orders --format=csv --where=created_at:2025-06-01
```

### 4. Seed Development Database
```bash
# Export sample data
php artisan db:export products --limit=10 --output=sample_products.json
php artisan db:export users --limit=5 --output=sample_users.json

# Import to fresh database
php artisan db:import products sample_products.json
php artisan db:import users sample_users.json
```

## Safety Features

1. **Validation**: Use `--validate` to check data compatibility before importing
2. **Truncate Confirmation**: Requires manual confirmation when using `--truncate`
3. **Column Checking**: Warns about missing or unknown columns
4. **Progress Bar**: Shows import progress for large files
5. **Error Handling**: Reports specific errors for failed chunks

## Limitations

1. **Relations**: Does not automatically handle foreign key relations
2. **File Size**: Large exports may require increased PHP memory limits
3. **Data Types**: Some complex data types may need manual adjustment
4. **Auto-increment**: ID columns are preserved; may conflict with existing data

## Tips

1. Always validate before importing to production
2. Use `--limit` for testing exports before full export
3. Keep backups before using `--truncate`
4. Use column mapping to handle schema differences
5. Check file permissions in storage directories