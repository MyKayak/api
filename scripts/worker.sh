#!/bin/bash

# Define timings
MONTH_SECONDS=$((30 * 24 * 60 * 60))
FIVE_MINUTES=$((5 * 60))

# We keep track of the last time we ran a full synchronization
last_full_sync=0

echo "Waiting for database to be ready..."
while ! php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" >/dev/null 2>&1; do
    echo "Database unavailable, waiting 5 seconds..."
    sleep 5
done
echo "Database is ready!"

while true; do
    current_time=$(date +%s)
    
    # Check if a month has passed since the last full sync
    if [ $((current_time - last_full_sync)) -ge $MONTH_SECONDS ]; then
        echo "[$(date)] Running full synchronization..."
        php /var/www/scripts/reset.php
        last_full_sync=$(date +%s)
    else
        echo "[$(date)] Running recent synchronization..."
        php /var/www/scripts/reset.php --recent
    fi
    
    echo "[$(date)] Sync complete. Sleeping for 5 minutes."
    sleep $FIVE_MINUTES
done
