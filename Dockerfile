# STEP 1: ใช้ PHP Image ที่มี Apache เป็น Base Image
# เราใช้ PHP 8.2 พร้อม Apache เพื่อให้เหมาะกับโปรเจกต์ของคุณ
FROM php:8.2-apache

# STEP 2: ตั้งค่า Working Directory ภายใน Container
# นี่คือ Root Folder ที่ไฟล์เว็บไซต์ของคุณจะถูกวางไว้ และ Apache จะใช้เป็น Document Root
WORKDIR /var/www/html

# STEP 3: คัดลอกโค้ด Application ทั้งหมดของคุณไปยัง Container
# COPY . /var/www/html หมายถึง คัดลอกทุกไฟล์/โฟลเดอร์จาก Current Directory (Root ของโปรเจกต์คุณ)
# ไปยัง /var/www/html ภายใน Container
COPY . /var/www/html

# STEP 4: ติดตั้ง System Dependencies ที่จำเป็นสำหรับ PHP Extensions และ Tools
# 'libpq-dev' จำเป็นสำหรับ pdo_pgsql และ pgsql extensions (สำหรับ PostgreSQL)
# 'zip' และ 'unzip' เป็นเครื่องมือที่พบบ่อยสำหรับ PHP applications/dependency managers (เช่น Composer)
# 'rm -rf /var/lib/apt/lists/*' เพื่อล้าง Cache ของ apt และลดขนาด Docker Image
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# STEP 5: ติดตั้ง PHP Extensions ที่โปรเจกต์ของคุณต้องการ
# 'pdo': PHP Data Objects Core (จำเป็นสำหรับ PDO ทุก Database)
# 'pdo_pgsql': PDO Driver สำหรับ PostgreSQL (สำหรับเชื่อมต่อ DB ของคุณ)
# 'pgsql': Native PostgreSQL Extension (บางที pdo_pgsql ก็พอ แต่ติดตั้งไปก็ไม่เสียหาย)
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# STEP 6: กำหนดค่า Apache เพื่อให้รองรับ .htaccess และเปิดใช้งาน Modules ที่จำเป็น
# 'sed -i' คำสั่งสำหรับแก้ไขไฟล์ apache2.conf โดยตรง:
# - 's/AllowOverride None/AllowOverride All/g': อนุญาตให้ .htaccess สามารถ Override การตั้งค่า Apache ได้
# - 'a2enmod rewrite': เปิดใช้งาน mod_rewrite (จำเป็นสำหรับ URL Rewrite)
# - 'a2enmod headers': เปิดใช้งาน mod_headers (จำเป็นสำหรับ Header always set ใน .htaccess)
# - 'a2enmod expires': เปิดใช้งาน mod_expires (จำเป็นสำหรับ Caching ใน .htaccess)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires

# STEP 7: ตั้งค่า Permission ของไฟล์/โฟลเดอร์สำหรับ Upload
# นี่เป็นส่วนสำคัญที่ทำให้ PHP สามารถเขียนไฟล์ (เช่น รูปภาพที่อัปโหลด) ลงใน Server ได้
# 'www-data' คือ User/Group ที่ Apache รันอยู่ภายใน Container
# 'chown -R www-data:www-data assets/img': เปลี่ยนเจ้าของ assets/img และทุกอย่างข้างใน
# 'find assets/img -type d -exec chmod 775 {} \;': ตั้งค่า Permissions สำหรับ Directories ให้ www-data มีสิทธิ์เขียนและอื่นๆ อ่าน/Execute
# 'find assets/img -type f -exec chmod 664 {} \;': ตั้งค่า Permissions สำหรับ Files ให้ www-data มีสิทธิ์เขียนและอื่นๆ อ่าน
RUN chown -R www-data:www-data assets/img \
    && find assets/img -type d -exec chmod 775 {} \; \
    && find assets/img -type f -exec chmod 664 {} \;

# STEP 8: กำหนด Directory Index ให้ Apache (ไฟล์เริ่มต้นที่ Apache จะมองหา)
# นี่จะช่วยแก้ปัญหา 'Not Found' บนหน้าหลัก (/) ถ้า index.php ไม่ถูกหาเจอ
RUN echo 'DirectoryIndex index.php index.html' > /etc/apache2/conf-enabled/z-directoryindex.conf

# STEP 9: กำหนด Port ที่ Container จะเปิดเพื่อรับ Traffic ภายนอก
# Apache โดยปกติจะรับ HTTP Traffic ที่ Port 80
EXPOSE 80

# STEP 10: กำหนด Command ที่จะรันเมื่อ Container เริ่มต้น (ไม่บังคับ เพราะ Base Image มี Default)
# CMD ["apache2-foreground"]