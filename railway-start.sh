#!/bin/bash

# Exit on error
set -e

echo "Starting Laravel application on Railway..."

# Create .env file if it doesn't exist
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env file from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Ensure database directory exists
mkdir -p /var/www/html/database

# Create SQLite database file if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force --no-interaction
    # Read the generated key from .env file
    export APP_KEY=$(grep APP_KEY /var/www/html/.env | cut -d '=' -f2)
    echo "Generated APP_KEY: $APP_KEY"
fi

# Configure for HTTPS if on Railway
echo "Configuring for HTTPS deployment..."

# Clear existing configuration first
> /tmp/.env.new

# Read existing .env and filter out duplicates
while IFS= read -r line; do
    key=$(echo "$line" | cut -d '=' -f1)
    case "$key" in
        APP_URL|ASSET_URL|APP_ENV|FORCE_HTTPS|L5_SWAGGER_GENERATE_ALWAYS)
            # Skip these as we'll add them fresh
            ;;
        *)
            echo "$line" >> /tmp/.env.new
            ;;
    esac
done < /var/www/html/.env

# Add our production configuration
cat >> /tmp/.env.new << EOF
APP_URL=https://test-adhivas-production.up.railway.app
ASSET_URL=https://test-adhivas-production.up.railway.app
APP_ENV=production
FORCE_HTTPS=true
L5_SWAGGER_GENERATE_ALWAYS=true
EOF

# Replace the .env file
mv /tmp/.env.new /var/www/html/.env

# Set environment variables for the current process
export APP_URL="https://test-adhivas-production.up.railway.app"
export ASSET_URL="https://test-adhivas-production.up.railway.app"
export APP_ENV="production"
export FORCE_HTTPS="true"
export L5_SWAGGER_GENERATE_ALWAYS="true"

# Ensure proper directories exist for l5-swagger with correct permissions
echo "Setting up l5-swagger directories..."
mkdir -p /var/www/html/storage/api-docs
mkdir -p /var/www/html/public/docs
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/public/docs

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Handle storage link - remove if exists and recreate
echo "Setting up storage link..."
if [ -L /var/www/html/public/storage ]; then
    echo "Removing existing storage link..."
    rm /var/www/html/public/storage
fi
php artisan storage:link

# Clear all caches before generating swagger
echo "Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Debug: Show current configuration
echo "Current APP_URL: $(php artisan tinker --execute="echo config('app.url');")"

# Generate l5-swagger documentation with better error handling
echo "=== GENERATING SWAGGER DOCUMENTATION ==="

# First, publish l5-swagger config if not exists
if [ ! -f /var/www/html/config/l5-swagger.php ]; then
    echo "Publishing l5-swagger configuration..."
    php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
fi

# Check if l5-swagger commands are available
echo "Available swagger commands:"
php artisan list | grep swagger || echo "⚠️  No swagger commands found"

# Try multiple approaches to generate swagger docs
echo "Attempting to generate Swagger documentation..."

# Method 1: Direct generation
php artisan l5-swagger:generate --all 2>&1 && echo "✅ Method 1 successful" || echo "❌ Method 1 failed"

# Check what was actually generated
echo "=== CHECKING GENERATED FILES ==="
echo "Contents of storage directory:"
find /var/www/html/storage -name "*.json" -type f 2>/dev/null || echo "No JSON files in storage"

echo "Contents of public directory:"
find /var/www/html/public -name "*swagger*" -o -name "*doc*" 2>/dev/null || echo "No swagger/docs files in public"

# Look for the actual location of generated docs
echo "Searching for api-docs.json:"
find /var/www/html -name "api-docs.json" 2>/dev/null || echo "api-docs.json not found anywhere"

# Create a simple test API docs if generation failed
if [ ! -f /var/www/html/storage/api-docs/api-docs.json ]; then
    echo "⚠️  Creating fallback API documentation..."
    mkdir -p /var/www/html/storage/api-docs
    cat > /var/www/html/storage/api-docs/api-docs.json << 'EOF'
{
    "openapi": "3.0.0",
    "info": {
        "title": "Laravel Book Management API",
        "version": "1.0.0",
        "description": "API documentation for Book Management System"
    },
    "servers": [
        {
            "url": "https://test-adhivas-production.up.railway.app",
            "description": "Production Server"
        }
    ],
    "paths": {
        "/api/health": {
            "get": {
                "summary": "Health check endpoint",
                "responses": {
                    "200": {
                        "description": "API is working"
                    }
                }
            }
        }
    }
}
EOF
    echo "✅ Fallback documentation created"
fi

# Verify final state
if [ -f /var/www/html/storage/api-docs/api-docs.json ]; then
    echo "✅ Final check: api-docs.json exists"
    echo "File size: $(wc -c < /var/www/html/storage/api-docs/api-docs.json) bytes"
    echo "First few lines:"
    head -n 5 /var/www/html/storage/api-docs/api-docs.json
else
    echo "❌ Final check: api-docs.json still missing"
fi

# Ensure the api-docs.json file is accessible in the public directory
if [ -f /var/www/html/storage/api-docs/api-docs.json ]; then
    echo "Copying api-docs.json to public/docs..."
    cp /var/www/html/storage/api-docs/api-docs.json /var/www/html/public/docs/api-docs.json
    chmod 644 /var/www/html/public/docs/api-docs.json
else
    echo "⚠️  api-docs.json not found in storage/api-docs."
fi

# Create a simple health check route if none exists
php artisan tinker --execute="
if (!Route::has('api.health')) {
    try {
        Route::get('/api/health', function() {
            return response()->json(['status' => 'OK', 'timestamp' => now()]);
        })->name('api.health');
        echo 'Health check route added';
    } catch (Exception \$e) {
        echo 'Could not add health route: ' . \$e->getMessage();
    }
}
" 2>/dev/null || true

# Cache configuration for better performance (after swagger generation)
echo "Caching configuration..."
php artisan route:cache
php artisan view:cache

# Final diagnostics
echo "=== FINAL DIAGNOSTICS ==="
echo "Available routes containing 'doc' or 'swagger':"
php artisan route:list

echo "L5Swagger configuration:"
php artisan config:show l5-swagger.default 2>/dev/null | head -n 10 || echo "L5Swagger config not accessible"

echo "File permissions:"
ls -la /var/www/html/storage/api-docs/ 2>/dev/null || echo "api-docs directory not accessible"

# Set default port if not provided by Railway
PORT=${PORT:-8000}

echo "=== STARTING SERVER ==="
echo "Server starting on port $PORT..."

# Start PHP built-in server
php artisan serve --host=0.0.0.0 --port=$PORT
