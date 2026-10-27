FROM php:8.3-fpm

# 1. Cài đặt các thư viện hệ thống (bao gồm PostgreSQL và Node.js để build Tailwind/Vite)
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
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring gd

# 2. Copy Composer từ Image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 3. Copy toàn bộ mã nguồn
COPY . .

# 4. Copy file cấu hình Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# 5. Cài đặt các thư viện PHP via Composer
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs

# 6. Build file giao diện Frontend (Tailwind / Vite / Assets)
RUN npm install && npm run build

# 7. Phân quyền lưu trữ cho Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# 8. Lệnh khởi chạy server chuẩn (xóa cache và bật PHP-FPM + Nginx)
CMD ["sh", "-c", "APP_PORT=${PORT:-10000}; sed -i \"s/listen 80;/listen ${APP_PORT};/\" /etc/nginx/sites-available/default; php artisan config:clear || true; php artisan migrate --force || echo 'Migration failed; application will start so the error remains visible in logs'; php artisan optimize:clear || true; php artisan optimize || true; php-fpm -D; exec nginx -g 'daemon off;'"]
