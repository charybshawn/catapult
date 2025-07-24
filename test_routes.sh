#!/bin/bash

# Array of admin routes to test (excluding login/logout and create/edit forms that need auth)
routes=(
    "admin"
    "admin/activities"
    "admin/categories" 
    "admin/consumables"
    "admin/crop-alerts"
    "admin/crop-batches"
    "admin/crop-plans"
    "admin/crops"
    "admin/customers"
    "admin/harvests"
    "admin/invoices"
    "admin/master-cultivars"
    "admin/master-seed-catalogs"
    "admin/orders"
    "admin/packaging-types"
    "admin/price-variations"
    "admin/product-inventories"
    "admin/product-mixes"
    "admin/product-photos"
    "admin/products"
    "admin/recipes"
    "admin/recurring-orders"
    "admin/scheduled-tasks"
    "admin/seed-entries"
    "admin/seed-scrape-uploads"
    "admin/seed-variations"
    "admin/seeds"
    "admin/settings"
    "admin/suppliers"
    "admin/tasks"
    "admin/time-cards"
    "admin/users"
)

echo "Testing admin routes for 500 errors..."
echo "============================================"

errors_found=0

for route in "${routes[@]}"; do
    echo -n "Testing /$route ... "
    status=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000/$route")
    
    if [ "$status" = "500" ]; then
        echo "❌ ERROR 500"
        errors_found=$((errors_found + 1))
    elif [ "$status" = "302" ]; then
        echo "✓ OK (redirect - likely needs auth)"
    elif [ "$status" = "200" ]; then
        echo "✓ OK"
    else
        echo "⚠️  Status: $status"
    fi
done

echo "============================================"
echo "Found $errors_found routes with 500 errors"