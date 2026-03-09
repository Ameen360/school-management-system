# Use official PHP with Apache
FROM php:8.2-apache

# Install mysqli and pdo_mysql extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (needed for most PHP apps)
RUN a2enmod rewrite

# Copy all your project files into the container
COPY . /var/www/html/

# Give Apache permission to read files
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80