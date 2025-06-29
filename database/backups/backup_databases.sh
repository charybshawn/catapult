#!/bin/bash

# Database backup script for catapult project
BACKUP_DIR="/Users/shawn/Documents/GitHub/catapult/database/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Creating database backups..."

# Backup main branch database (catapult)
echo "Backing up main branch database (catapult)..."
mysqldump -h 127.0.0.1 -u root \
  --single-transaction \
  --routines \
  --triggers \
  --add-drop-table \
  --extended-insert \
  --ignore-table=catapult.product_inventory_summary \
  catapult > "${BACKUP_DIR}/catapult_main_${TIMESTAMP}.sql" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✓ Main database backup completed: catapult_main_${TIMESTAMP}.sql"
else
    echo "✗ Main database backup failed"
fi

# Backup develop branch database (catapult-dev) 
echo "Backing up develop branch database (catapult-dev)..."
mysqldump -h 127.0.0.1 -u root \
  --single-transaction \
  --routines \
  --triggers \
  --add-drop-table \
  --extended-insert \
  catapult-dev > "${BACKUP_DIR}/catapult_dev_${TIMESTAMP}.sql" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✓ Develop database backup completed: catapult_dev_${TIMESTAMP}.sql"
else
    echo "✗ Develop database backup failed"
fi

# Create data-only exports for main branch (for migration)
echo "Creating data-only exports for main branch tables with data..."
for table in users products crops recipes seed_entries seed_variations seed_price_history seed_scrape_uploads supplier_source_mappings; do
    mysqldump -h 127.0.0.1 -u root \
        --no-create-info \
        --complete-insert \
        --extended-insert=FALSE \
        catapult $table > "${BACKUP_DIR}/main_data_${table}_${TIMESTAMP}.sql" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "  ✓ Exported data for table: $table"
    fi
done

echo "Backup process completed!"
echo "Files created in: ${BACKUP_DIR}"
ls -la "${BACKUP_DIR}"/*_${TIMESTAMP}.sql | awk '{print "  - " $9}'