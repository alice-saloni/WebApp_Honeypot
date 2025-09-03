
FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set PHP display_errors to On for debugging
RUN echo "display_errors=On" >> /usr/local/etc/php/php.ini

# Copy application files
COPY ./public /var/www/html

# Expose port 80
EXPOSE 80
