#!/bin/sh
# Post-startup optimization script
# This runs after services are up to optimize performance

echo "🔹 Starting post-startup optimizations..."

# Wait for services to be ready with shorter timeout
echo "🔹 Waiting for services to be ready..."
timeout=30
counter=0

# Quick check for PHP-FPM
while ! nc -z localhost 9000; do
  sleep 0.5
  counter=$((counter + 1))
  if [ $counter -ge $((timeout * 2)) ]; then
    echo "⚠️ PHP-FPM not ready, skipping optimizations"
    exit 0
  fi
done

# Quick check for Nginx
counter=0
while ! nc -z localhost 80; do
  sleep 0.5
  counter=$((counter + 1))
  if [ $counter -ge $((timeout * 2)) ]; then
    echo "⚠️ Nginx not ready, skipping optimizations"
    exit 0
  fi
done

echo "✅ Services are ready, starting optimizations..."

# Give services a moment to fully initialize
sleep 2

cd /var/www/html

# Only cache config and routes if they don't already exist or are very small
echo "🔹 Optimizing caches..."

# Cache config only if it's beneficial
if [ ! -f bootstrap/cache/config.php ] || [ $(stat -c%s bootstrap/cache/config.php 2>/dev/null || echo 0) -lt 100 ]; then
    php artisan config:cache 2>/dev/null && echo "✅ Config cached" || echo "⚠️ Config cache skipped"
fi

# Cache routes only if routes file exists and is substantial
if [ -f routes/api.php ] && [ $(wc -l < routes/api.php) -gt 10 ]; then
    php artisan route:cache 2>/dev/null && echo "✅ Routes cached" || echo "⚠️ Route cache skipped"
fi

# View cache is usually not critical for APIs
php artisan view:cache 2>/dev/null && echo "✅ Views cached" || echo "⚠️ View cache skipped"

# Run initial match sync so results are available immediately on startup
echo "🔹 Running initial match sync..."
php artisan quiniela:sync --recalculate 2>/dev/null && echo "✅ Initial sync complete" || echo "⚠️ Initial sync failed (will retry via scheduler)"

echo "✅ Post-startup operations completed successfully"
