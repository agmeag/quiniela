#!/bin/bash
set -euo pipefail

# Zero-downtime, signal-aware startup

readonly APP_ENV="${APP_ENV:-production}"
readonly LOG_LEVEL="${LOG_LEVEL:-info}"

# Logging functions
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] [INFO] $*" >&2
}

error() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] [ERROR] $*" >&2
}

# Signal handlers for graceful shutdown
cleanup() {
    log "Received shutdown signal, cleaning up..."

    # Stop Laravel queues gracefully
    if pgrep -f "artisan queue:work" >/dev/null; then
        log "Stopping Laravel queue workers..."
        pkill -TERM -f "artisan queue:work" || true
        sleep 2
    fi

    # Stop supervisord gracefully
    if pgrep supervisord >/dev/null; then
        log "Stopping supervisord..."
        supervisorctl shutdown || true
    fi

    log "Cleanup completed"
    exit 0
}

# Set up signal traps
trap cleanup SIGTERM SIGINT SIGQUIT

# Pre-flight checks
preflight_checks() {
    log "Performing pre-flight checks..."

    # Check if we can write to storage
    if ! touch /var/www/html/storage/logs/startup.log 2>/dev/null; then
        error "Cannot write to storage directory"
        exit 1
    fi

    # Check PHP syntax
    if ! php -l /var/www/html/artisan >/dev/null 2>&1; then
        error "PHP syntax errors detected"
        exit 1
    fi

    log "Pre-flight checks passed"
}

# Environment setup
setup_environment() {
    log "Setting up environment..."

    # Create .env if it doesn't exist
    if [[ ! -f /var/www/html/.env ]]; then
        if [[ -f /var/www/html/.env.example ]]; then
            cp /var/www/html/.env.example /var/www/html/.env
            log "Created .env from example"
        else
            error ".env file missing and no .env.example found"
            exit 1
        fi
    fi

    # Ensure directories exist with correct permissions
    mkdir -p \
        storage/logs \
        storage/framework/{cache,sessions,views} \
        bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

    log "Environment setup completed"
}

# Laravel optimization
optimize_laravel() {
    log "Optimizing Laravel application..."

    # Only clear caches, don't rebuild during startup for speed
    php artisan config:clear --quiet || true
    php artisan route:clear --quiet || true
    php artisan view:clear --quiet || true

    # Generate application key if needed
    if grep -q "APP_KEY=$" /var/www/html/.env 2>/dev/null; then
        log "Generating application key..."
        php artisan key:generate --force --quiet
    fi

    log "Laravel optimization completed"
}

# Start services
start_services() {
    log "Starting services..."

    # Start supervisord in foreground
    exec supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
}

# Main execution
main() {
    log "Starting Laravel application (PID: $$)"
    log "Environment: ${APP_ENV}"

    # Fix permissions
    # Since we are root, we can chown the mounted volumes
    log "Fixing permissions for storage and bootstrap/cache..."
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

    # Drop to www-data for application setup
    # We use su to run these commands as www-data so created files have correct ownership
    log "Running application setup as www-data..."
    su www-data -s /bin/bash -c "
        if [ ! -f /var/www/html/.env ]; then
            if [ -f /var/www/html/.env.example ]; then
                cp /var/www/html/.env.example /var/www/html/.env
            fi
        fi
        
        # Optimization
        php artisan config:clear --quiet || true
        php artisan route:clear --quiet || true
        php artisan view:clear --quiet || true
        
        if grep -q \"APP_KEY=$\" /var/www/html/.env 2>/dev/null; then
             php artisan key:generate --force --quiet
        fi
    "

    # Start services (Supervisord runs as root but spawns workers as appuser)
    start_services
}

# Run main function
main "$@"
