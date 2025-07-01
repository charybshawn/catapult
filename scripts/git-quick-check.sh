#!/bin/bash

# Lightweight git check script - runs every minute
# Only checks for changes, triggers full pull script if needed

# Set working directory
cd /var/www/catapult

# Log file for quick checks (separate from main log)
LOG_FILE="/var/www/catapult/storage/logs/git-quick-check.log"
MAIN_SCRIPT="/var/www/catapult/scripts/git-auto-pull.sh"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Quick fetch with timeout (3 seconds)
timeout 3 git fetch origin main --quiet 2>/dev/null

if [ $? -eq 0 ]; then
    # Check if there are any changes
    LOCAL=$(git rev-parse main 2>/dev/null)
    REMOTE=$(git rev-parse origin/main 2>/dev/null)
    
    if [ -n "$LOCAL" ] && [ -n "$REMOTE" ] && [ "$LOCAL" != "$REMOTE" ]; then
        log_message "Changes detected - triggering full pull"
        # Run the full pull script
        $MAIN_SCRIPT
    fi
else
    log_message "Quick check failed or timed out"
fi