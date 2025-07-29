# STEP 1: Use an official PHP image with Apache as the base
FROM php:8.2-apache

# STEP 2: Set the working directory inside the container
# This is the default document root for Apache (where your web files will go)
WORKDIR /var/www/html

# STEP 3: Copy your entire application code into the container
# The '.' means copy everything from the current directory on your local machine
# to the WORKDIR '/var/www/html' inside the container.
COPY . /var/www/html

# STEP 4: Install necessary system dependencies for PHP extensions (like PostgreSQL client libraries)
# 'libpq-dev' is required for pdo_pgsql and pgsql extensions.
# 'zip' and 'unzip' are common tools often used by PHP applications/dependency managers.
# 'rm -rf /var/lib/apt/lists/*' cleans up apt cache to keep the image size small.
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# STEP 5: Install PHP extensions required by your application
# 'pdo' is the PHP Data Objects extension core.
# 'pdo_pgsql' is the PDO driver for PostgreSQL.
# 'pgsql' is the native PostgreSQL extension for PHP.
# The 'docker-php-ext-install' command compiles and enables the extensions.
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# STEP 6: Configure Apache to allow .htaccess overrides and enable rewrite module
# 'sed -i' command modifies the apache2.conf file in-place:
# It changes 'AllowOverride None' to 'AllowOverride All' for the /var/www/html directory,
# which allows your .htaccess file (if you use one for clean URLs) to work.
# 'a2enmod rewrite' enables Apache's mod_rewrite module, also necessary for .htaccess.
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# STEP 7: Set correct file permissions for directories where files might be uploaded
# This is crucial for PHP's move_uploaded_file() function to work.
# 'www-data' is the default user Apache runs as in this Docker image.
# 'chown -R www-data:www-data assets/img': Changes ownership of assets/img and its contents.
# 'find assets/img -type d -exec chmod 775 {} \;': Sets read/write/execute for owner/group on directories.
# 'find assets/img -type f -exec chmod 664 {} \;': Sets read/write for owner/group on files.
# This ensures Apache has permission to create/delete files in these directories.
RUN chown -R www-data:www-data assets/img \
    && find assets/img -type d -exec chmod 775 {} \; \
    && find assets/img -type f -exec chmod 664 {} \;

# STEP 8: Expose the port that Apache is listening on inside the container
# Apache typically listens on port 80 for HTTP traffic.
EXPOSE 80

# STEP 9: Define the default command to run when the container starts
# For php:apache images, Apache is usually the entry point.
# This command is typically implicitly handled by the base image, so it's often omitted.
# CMD ["apache2-foreground"]