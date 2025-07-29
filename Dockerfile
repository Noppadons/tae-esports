# STEP 1: ใช้ PHP Image ที่มี Apache เป็น Base Image
FROM php:8.2-apache

# STEP 2: ตั้งค่า Working Directory ภายใน Container
WORKDIR /var/www/html

# STEP 3: คัดลอกโค้ด Application ทั้งหมดของคุณไปยัง Container
COPY . /var/www/html

# STEP 4: ติดตั้ง System Dependencies ที่จำเป็นสำหรับ PHP Extensions และ Tools
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# STEP 5: ติดตั้ง PHP Extensions ที่โปรเจกต์ของคุณต้องการ
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# STEP 6: กำหนดค่า Apache สำหรับการอนุญาต Override (ไม่ใช้ .htaccess สำหรับฟังก์ชันขั้นสูงแล้ว)
# แต่ยังคงรักษา AllowOverride All เพื่อให้ Apache อ่าน .htaccess ได้หากมีกฎพื้นฐานอื่นๆ
# และเปิด mod_rewrite เผื่อกรณีจำเป็น (แต่จะไม่ใช้สำหรับ URL สวยงามแล้ว)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

# STEP 7: ตั้งค่า Permission ของไฟล์/โฟลเดอร์สำหรับ Upload
RUN chown -R www-data:www-data assets/img \
    && find assets/img -type d -exec chmod 775 {} \; \
    && find assets/img -type f -exec chmod 664 {} \;

# STEP 8: กำหนด Directory Index ให้ Apache (ไฟล์เริ่มต้นที่ Apache จะมองหา)
# นี่จะช่วยแก้ปัญหา 'Not Found' บนหน้าหลัก (/) ถ้า index.php ไม่ถูกหาเจอ
RUN echo 'DirectoryIndex index.php index.html' > /etc/apache2/conf-enabled/z-directoryindex.conf

# STEP 9: กำหนด Port ที่ Container จะเปิดเพื่อรับ Traffic ภายนอก
EXPOSE 80

# STEP 10: กำหนด Command ที่จะรันเมื่อ Container เริ่มต้น (ไม่บังคับ เพราะ Base Image มี Default)
# CMD ["apache2-foreground"]