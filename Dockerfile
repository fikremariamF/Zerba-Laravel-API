# Use the official PHP image with Apache
FROM php:8.2-apache

# Set the working directory in the container to /var/www/html
WORKDIR /var/www/html

# Install PHP extensions required for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set permissions for the Laravel app
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Copy the existing application directory contents to the container
COPY . /var/www/html

# Expose port 80 to the Docker host, so we can access it 
# from the outside.
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
