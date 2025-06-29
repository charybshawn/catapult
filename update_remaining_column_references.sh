#!/bin/bash

# Update remaining column references to match main branch names

echo "Starting comprehensive column reference update..."

# Define the search and replace patterns
declare -A replacements=(
    # Seed variations columns
    ["size_description"]="size"
    ["is_in_stock"]="is_available"
    
    # Seed entries columns  
    ["supplier_product_title"]="supplier_sku"
    ["supplier_product_url"]="url"
    
    # Seed price history columns
    ["scraped_at"]="checked_at"
    
    # Seed scrape uploads columns
    ["original_filename"]="filename"
    ["successful_entries"]="new_entries"
    
    # Crops columns
    ["time_to_next_stage_status"]="time_to_next_stage_display"
    ["stage_age_status"]="stage_age_display"
    ["total_age_status"]="total_age_display"
)

# Function to perform replacements
update_files() {
    local pattern=$1
    local replacement=$2
    
    echo "Replacing '$pattern' with '$replacement'..."
    
    # Find all PHP files containing the pattern
    files=$(find . -name "*.php" -type f -not -path "./vendor/*" -not -path "./storage/*" -not -path "./node_modules/*" -exec grep -l "$pattern" {} \;)
    
    if [ -z "$files" ]; then
        echo "  No files found containing '$pattern'"
        return
    fi
    
    # Update each file
    for file in $files; do
        # Skip migration files except for the ones we're specifically updating
        if [[ $file == *"/database/migrations/"* ]]; then
            if [[ ! $file =~ (2025_06_25_233253_update_seed_variations_table_for_import|2025_06_25_233329_update_seed_price_history_table_for_import|2025_06_25_233359_update_seed_scrape_uploads_table_for_import|2025_06_25_233222_add_missing_columns_to_seed_entries_table|2025_06_28_192811_fix_crops_column_names) ]]; then
                echo "  Skipping migration file: $file"
                continue
            fi
        fi
        
        echo "  Updating: $file"
        
        # Create backup
        cp "$file" "$file.bak"
        
        # Perform the replacement
        # Use word boundaries to avoid partial matches
        sed -i '' "s/\b${pattern}\b/${replacement}/g" "$file"
        
        # Check if file was modified
        if ! diff -q "$file" "$file.bak" > /dev/null; then
            echo "    ✓ Updated"
            rm "$file.bak"
        else
            echo "    - No changes"
            rm "$file.bak"
        fi
    done
}

# Perform all replacements
for pattern in "${!replacements[@]}"; do
    update_files "$pattern" "${replacements[$pattern]}"
    echo ""
done

# Update Blade template files
echo "Updating Blade template files..."
find ./resources/views -name "*.blade.php" -type f -exec grep -l "size_description\|is_in_stock\|supplier_product_title\|supplier_product_url\|scraped_at\|original_filename\|successful_entries" {} \; | while read file; do
    echo "  Updating: $file"
    cp "$file" "$file.bak"
    
    sed -i '' 's/size_description/size/g' "$file"
    sed -i '' 's/is_in_stock/is_available/g' "$file"
    sed -i '' 's/supplier_product_title/supplier_sku/g' "$file"
    sed -i '' 's/supplier_product_url/url/g' "$file"
    sed -i '' 's/scraped_at/checked_at/g' "$file"
    sed -i '' 's/original_filename/filename/g' "$file"
    sed -i '' 's/successful_entries/new_entries/g' "$file"
    
    if ! diff -q "$file" "$file.bak" > /dev/null; then
        echo "    ✓ Updated"
        rm "$file.bak"
    else
        echo "    - No changes"
        rm "$file.bak"
    fi
done

echo ""
echo "Column reference update complete!"
echo ""

# Show summary of changes
echo "Summary of changes:"
echo "- size_description → size"
echo "- is_in_stock → is_available"
echo "- supplier_product_title → supplier_sku"
echo "- supplier_product_url → url"
echo "- scraped_at → checked_at"
echo "- original_filename → filename"
echo "- successful_entries → new_entries"
echo "- time_to_next_stage_status → time_to_next_stage_display"
echo "- stage_age_status → stage_age_display"
echo "- total_age_status → total_age_display"