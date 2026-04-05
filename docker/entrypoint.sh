#!/bin/bash
set -e

echo "╔══════════════════════════════════════╗"
echo "║   CrewAssist Portal — Starting...    ║"
echo "╚══════════════════════════════════════╝"

# Wait for MySQL to be ready
if [ "$DB_DRIVER" != "sqlite" ]; then
    echo "⏳ Waiting for MySQL ($DB_HOST:$DB_PORT)..."
    MAX_RETRIES=30
    RETRY=0
    until php -r "
        try {
            new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), 
                     getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            echo 'connected';
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        RETRY=$((RETRY + 1))
        if [ $RETRY -ge $MAX_RETRIES ]; then
            echo "❌ MySQL not reachable after $MAX_RETRIES attempts"
            exit 1
        fi
        echo "  Attempt $RETRY/$MAX_RETRIES..."
        sleep 2
    done
    echo "✅ MySQL connection established"
fi

# Generate production .env if not exists
if [ ! -f /var/www/html/.env ]; then
    echo "📄 Generating .env from environment variables..."
    cat > /var/www/html/.env << EOF
APP_NAME=CrewAssist Portal
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_KEY=${APP_KEY:-$(openssl rand -hex 16)}
APP_MODE=${APP_MODE:-multi_tenant}
FIXED_TENANT_ID=${FIXED_TENANT_ID:-}
DB_DRIVER=${DB_DRIVER:-mysql}
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-crewassist}
DB_USERNAME=${DB_USERNAME:-crewassist}
DB_PASSWORD=${DB_PASSWORD:-crewassist_pass}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_SECURE=${SESSION_SECURE:-true}
API_TOKEN_EXPIRY_HOURS=${API_TOKEN_EXPIRY_HOURS:-168}
API_RATE_LIMIT=${API_RATE_LIMIT:-60}
UPLOAD_MAX_SIZE=${UPLOAD_MAX_SIZE:-52428800}
UPLOAD_ALLOWED_TYPES=${UPLOAD_ALLOWED_TYPES:-pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif}
LOG_LEVEL=${LOG_LEVEL:-warning}
EOF
    chown www-data:www-data /var/www/html/.env
fi

# Run database setup/migration
echo "🗃️  Running database setup..."
php /var/www/html/setup.php 2>&1 || echo "⚠ Setup completed with warnings"

# Ensure storage directories exist and have correct permissions
mkdir -p /var/www/html/storage/{uploads,logs}
chown -R www-data:www-data /var/www/html/storage

echo ""
echo "✅ CrewAssist Portal ready!"
echo "   Environment: ${APP_ENV:-production}"
echo "   Database: ${DB_DRIVER:-mysql}://${DB_HOST:-db}:${DB_PORT:-3306}/${DB_DATABASE:-crewassist}"
echo ""

# Execute the main command (apache2-foreground)
exec "$@"
