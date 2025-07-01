#!/bin/bash

# Git auto-pull script for Catapult project
# Runs every 5 minutes via cron to check for updates on main branch

# Set working directory
cd /var/www/catapult

# Log file
LOG_FILE="/var/www/catapult/storage/logs/git-auto-pull.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Start
log_message "Starting git auto-pull check"

# Fetch latest changes from remote
git fetch origin main &>> "$LOG_FILE"

# Check if there are any changes
LOCAL=$(git rev-parse main)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" != "$REMOTE" ]; then
    log_message "Updates found on remote main branch"
    
    # Check for uncommitted changes
    if ! git diff-index --quiet HEAD --; then
        log_message "ERROR: Uncommitted changes detected. Skipping pull."
        exit 1
    fi
    
    # Pull latest changes
    log_message "Pulling latest changes from main branch"
    git pull origin main &>> "$LOG_FILE"
    
    if [ $? -eq 0 ]; then
        log_message "Successfully pulled latest changes"
        
        # Run composer install if composer.lock changed
        if git diff --name-only HEAD@{1} HEAD | grep -q "composer.lock"; then
            log_message "composer.lock changed, running composer install"
            composer install --no-interaction &>> "$LOG_FILE"
        fi
        
        # Run npm install if package-lock.json changed
        if git diff --name-only HEAD@{1} HEAD | grep -q "package-lock.json"; then
            log_message "package-lock.json changed, running npm install"
            npm install &>> "$LOG_FILE"
        fi
        
        # Clear Laravel caches
        log_message "Clearing Laravel caches"
        php artisan optimize:clear &>> "$LOG_FILE"
        
        # Run migrations if any
        log_message "Running migrations"
        php artisan migrate --force &>> "$LOG_FILE"
        
    else
        log_message "ERROR: Failed to pull changes"
        exit 1
    fi
else
    log_message "No updates found - repository is up to date"
fi

log_message "Git auto-pull check completed"