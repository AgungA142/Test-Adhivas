# Use PHP CLI image (Railway friendly)
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip exif pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies WITHOUT running scripts (to avoid artisan dependency)
RUN composer install --no-scripts --no-autoloader --optimize-autoloader

#prevent the storage link from beeing copied
RUN rm -rf /var/www/html/public/storage

# Copy application files
COPY . .

# Complete composer install with autoloader and scripts
RUN composer install --optimize-autoloader

# Create .env file from .env.production template for Railway
RUN if [ -f .env.production ]; then \
        cp .env.production .env; \
    elif [ -f .env.example ]; then \
        cp .env.example .env; \
    else \
        echo "APP_KEY=" > .env; \
    fi

# Create SQLite database file
RUN touch /var/www/html/database/database.sqlite

# Create necessary directories for l5-swagger
RUN mkdir -p /var/www/html/storage/api-docs \
    && mkdir -p /var/www/html/public/docs

# Set permissions
RUN chmod -R 755 /var/www/html \
    && chmod 664 /var/www/html/database/database.sqlite \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/public/docs

# Create startup script
COPY railway-start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Railway uses PORT environment variable
EXPOSE $PORT

# Use startup script
CMD ["/usr/local/bin/start.sh"]
