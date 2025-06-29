#!/bin/bash

# Script to update column references to match main branch naming

echo "Updating column references to match main branch naming..."

# Define replacements
declare -A replacements=(
    ["size_description"]="size"
    ["is_in_stock"]="is_available"
    ["scraped_at"]="checked_at"
    ["original_filename"]="filename"
    ["successful_entries"]="new_entries"
    ["original_weight_unit"]="unit"
)

# Function to update PHP files
update_php_files() {
    local dir=$1
    echo "Updating PHP files in: $dir"
    
    for old in "${!replacements[@]}"; do
        new="${replacements[$old]}"
        echo "  Replacing '$old' with '$new'"
        
        # Find and replace in PHP files
        find "$dir" -name "*.php" -type f -exec grep -l "$old" {} \; | while read file; do
            echo "    Updating: $file"
            # Create backup
            cp "$file" "$file.bak"
            
            # Replace column references
            sed -i '' "s/'$old'/'$new'/g" "$file"
            sed -i '' "s/\"$old\"/\"$new\"/g" "$file"
            sed -i '' "s/->$old/->$new/g" "$file"
            sed -i '' "s/\['$old'\]/['$new']/g" "$file"
            sed -i '' "s/\[\"$old\"\]/[\"$new\"]/g" "$file"
            
            # For method calls and property access
            sed -i '' "s/\$$old/\$$new/g" "$file"
        done
    done
}

# Update app directory
update_php_files "/Users/shawn/Documents/GitHub/catapult/app"

# Update database/seeders directory
update_php_files "/Users/shawn/Documents/GitHub/catapult/database/seeders"

# Update config files if any
update_php_files "/Users/shawn/Documents/GitHub/catapult/config"

echo "Column reference updates completed!"
echo "Note: Virtual columns were added to migrations for backward compatibility."
echo "Please review the changes and test thoroughly."

# List files that were updated
echo -e "\nFiles that were updated:"
find /Users/shawn/Documents/GitHub/catapult -name "*.php.bak" -type f | sed 's/.bak$//' | sort | uniq