#!/bin/sh
set -e

echo "============================================"
echo "  HRIS API - Production Entrypoint"
echo "============================================"

# -------------------------------------------
# 1. Wait for MySQL to be ready
# -------------------------------------------
echo "[*] Waiting for MySQL..."
MAX_RETRIES=30
RETRY_COUNT=0

until mysqladmin ping -h"${DB_HOST:-mysql}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD}" --silent 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ "$RETRY_COUNT" -ge "$MAX_RETRIES" ]; then
        echo "[!] MySQL not ready after ${MAX_RETRIES} attempts. Starting anyway..."
        break
    fi
    echo "    Waiting for MySQL... (attempt ${RETRY_COUNT}/${MAX_RETRIES})"
    sleep 2
done

echo "[✓] MySQL is ready!"

# -------------------------------------------
# 2. Create log directory
# -------------------------------------------
mkdir -p /var/log/php
chown www-data:www-data /var/log/php

# -------------------------------------------
# 3. Run database migrations
# -------------------------------------------
echo "[*] Running database migrations..."
php artisan migrate --force --no-interaction
echo "[✓] Migrations complete!"

# -------------------------------------------
# 4. Cache configuration for performance
# -------------------------------------------
echo "[*] Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo "[✓] Configuration cached!"

# -------------------------------------------
# 5. Create storage link if not exists
# -------------------------------------------
echo "[*] Creating storage link..."
php artisan storage:link --force 2>/dev/null || true
echo "[✓] Storage link ready!"

# -------------------------------------------
# 6. Sync Scout indexes (optional, won't fail)
# -------------------------------------------
echo "[*] Syncing search indexes..."
php artisan scout:sync-index-settings 2>/dev/null || true
echo "[✓] Search indexes synced!"

echo "============================================"
echo "  Starting application..."
echo "============================================"

# Execute the main command (supervisord)
exec "$@"
