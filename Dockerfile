# Use an official PHP image as a base
FROM php:8.2-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Copy your application code to the container
COPY . /var/www/html

# Install PostgreSQL client libraries and PHP extensions for PostgreSQL (pdo_pgsql)
# libpq-dev provides the necessary headers and development files for PostgreSQL client
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql pgsql
# RUN docker-php-ext-enable pdo_pgsql pgsql # บรรทัดนี้อาจไม่จำเป็น ถ้า install แล้ว enable เอง หรือถ้ามีปัญหาก็ค่อยใส่กลับไป

# Ensure Apache's rewrite module is enabled (for .htaccess if you use it)
RUN a2enmod rewrite

# Expose port 80 (Apache's default HTTP port)
EXPOSE 80