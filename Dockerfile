FROM php:8.3-fpm

# Cài đặt các thư viện hệ thống và PHP extension
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring gd

# Copy Composer từ image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy mã nguồn
COPY . .

# Cài đặt thư viện Laravel qua Composer
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs

# Phân quyền thư mục cho Laravel & Nginx
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cấu hình Nginx cho Laravel
RUN echo 'server { \
    listen 80; \
    root /var/www/html/public; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/sites-available/default

EXPOSE 80

# Tự động chạy migration, clear cache khi khởi chạy container
CMD ["sh", "-c", "php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"]