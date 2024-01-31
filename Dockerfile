FROM php:8.2-apache

# Install system dependencies and PHP extensions required for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd && \
    rm -rf /var/lib/apt/lists/*

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory to /var/www/html, the default Apache directory
WORKDIR /var/www/html

# Copy the application code to the container
COPY . /var/www/html

# Set the correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Use the default Apache foreground command to run Apache
CMD ["apache2-foreground"]
