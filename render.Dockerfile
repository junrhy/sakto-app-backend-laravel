FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    libpq-dev \
    postgresql-client \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Install NPM dependencies and build assets
RUN npm install && npm run build

# Configure nginx
RUN rm /etc/nginx/sites-enabled/default
COPY docker/nginx/app.conf /etc/nginx/conf.d/

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/vendor
RUN chmod -R 755 /var/www/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache

# Create startup script
RUN echo '#!/bin/sh\n\
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php artisan htmlpurifier:create-cache && \
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE -c "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";" && \
php artisan migrate --force && \
php artisan db:seed --force && \
php-fpm -D && \
nginx -g "daemon off;"\n' > /usr/local/bin/startup.sh \
&& chmod +x /usr/local/bin/startup.sh

# Expose port
EXPOSE 80

CMD ["/usr/local/bin/startup.sh"] 