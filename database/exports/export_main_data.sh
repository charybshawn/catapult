#!/bin/bash

# Export data from main branch database to import into develop branch

echo "Starting data export from main branch database..."

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
EXPORT_FILE="/Users/shawn/Documents/GitHub/catapult/database/exports/main_to_develop_data_${TIMESTAMP}.sql"

# Define tables to export (in dependency order)
TABLES=(
    "suppliers"
    "seed_entries" 
    "seed_variations"
    "seed_price_history"
    "seed_scrape_uploads"
    "supplier_source_mappings"
    "consumables"
    "recipes"
    "recipe_stages"
    "recipe_watering_schedule"
    "products"
    "product_photos"
    "packaging_types"
    "price_variations"
    "customers"
    "orders"
    "order_products"
    "invoices"
    "crops"
    "harvests"
    "task_types"
    "task_schedules"
    "crop_alerts"
    "product_inventories"
    "inventory_transactions"
    "inventory_reservations"
)

# Start the export file
echo "-- Data export from main branch database" > "$EXPORT_FILE"
echo "-- Generated at: $(date)" >> "$EXPORT_FILE"
echo "" >> "$EXPORT_FILE"

# Disable foreign key checks at the start
echo "SET FOREIGN_KEY_CHECKS=0;" >> "$EXPORT_FILE"
echo "" >> "$EXPORT_FILE"

# Export each table
for table in "${TABLES[@]}"; do
    echo "Exporting table: $table..."
    
    # Add table comment
    echo "-- Table: $table" >> "$EXPORT_FILE"
    
    # Truncate existing data
    echo "TRUNCATE TABLE \`$table\`;" >> "$EXPORT_FILE"
    
    # Export data (skip if table doesn't exist)
    mysqldump -h 127.0.0.1 -u root \
        --no-create-info \
        --complete-insert \
        --extended-insert \
        --single-transaction \
        --skip-comments \
        --skip-add-drop-table \
        --where="1 limit 100000" \
        catapult "$table" 2>/dev/null >> "$EXPORT_FILE" || echo "-- Table $table not found or empty" >> "$EXPORT_FILE"
    
    echo "" >> "$EXPORT_FILE"
done

# Re-enable foreign key checks at the end
echo "SET FOREIGN_KEY_CHECKS=1;" >> "$EXPORT_FILE"

echo ""
echo "Export complete!"
echo "File saved to: $EXPORT_FILE"
echo ""
echo "File size: $(ls -lh "$EXPORT_FILE" | awk '{print $5}')"
echo "Lines: $(wc -l < "$EXPORT_FILE")"