# Use an official PHP image as a base
FROM php:8.2-apache

# Set the working directory to /var/www/html
# This is the default document root for Apache
WORKDIR /var/www/html

# Copy your application code to the container
COPY . /var/www/html

# Ensure Apache's rewrite module is enabled (for .htaccess if you use it)
RUN a2enmod rewrite

# Expose port 80 (Apache's default HTTP port)
EXPOSE 80

# The default command for php:apache images is to start Apache
# CMD ["apache2-foreground"] # This is usually implicitly handled by the base image