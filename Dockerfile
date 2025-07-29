# Use an official PHP image as a base
FROM php:8.2-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Copy your application code to the container
COPY . /var/www/html

# Install PHP extensions for PostgreSQL (pdo_pgsql) and other common extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql
RUN docker-php-ext-enable pdo_pgsql pgsql # บางครั้งอาจไม่จำเป็นถ้า install แล้ว enable เอง

# Ensure Apache's rewrite module is enabled (for .htaccess if you use it)
RUN a2enmod rewrite

# Expose port 80 (Apache's default HTTP port)
EXPOSE 80