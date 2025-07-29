# Use an official PHP image as a base
FROM php:8.2-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Copy your application code to the container
COPY . /var/www/html

# Set permissions for upload directories to allow Apache to write
# The default user for Apache in this image is 'www-data'
RUN chown -R www-data:www-data assets/img \
    && chmod -R 755 assets/img \
    && find assets/img -type d -exec chmod 775 {} \; \
    && find assets/img -type f -exec chmod 664 {} \;
# อธิบาย:
# chown -R www-data:www-data assets/img : เปลี่ยนเจ้าของโฟลเดอร์และไฟล์ทั้งหมดใน assets/img ให้เป็น www-data:www-data (User ที่ Apache ใช้)
# chmod -R 755 assets/img : ตั้งค่า permissions เบื้องต้นให้ directories (rwxr-xr-x)
# find assets/img -type d -exec chmod 775 {} \; : ตั้งค่า permissions สำหรับ Directories ย่อยให้เขียนได้ (rwxrwxr-x)
# find assets/img -type f -exec chmod 664 {} \; : ตั้งค่า permissions สำหรับ Files ย่อยให้เขียนได้ (rw-rw-r--)
# (หาก 775/664 ยังไม่พอ อาจจะต้องลอง 777/666 ใน directory ที่ต้องเขียน แต่ 775/664 ปลอดภัยกว่า)

# Install PostgreSQL client libraries and PHP extensions for PostgreSQL (pdo_pgsql)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Ensure Apache's rewrite module is enabled (for .htaccess if you use it)
RUN a2enmod rewrite

# Expose port 80 (Apache's default HTTP port)
EXPOSE 80