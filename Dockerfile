FROM php:8.3-fpm

# Cài đặt thư viện hệ thống và PHP extension
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

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy toàn bộ mã nguồn
COPY . .

# Copy cấu hình Nginx chuẩn
COPY nginx.conf /etc/nginx/sites-available/default

# Cài đặt thư viện qua Composer
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs

# Phân quyền lưu trữ cho Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Chạy server Nginx + PHP-FPM an toàn
CMD php-fpm -D && nginx -g 'daemon off;'