# Sử dụng Nginx + PHP 8.3 chính thức
FROM richarvey/nginx-php-fpm:3.1.6

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Sao chép toàn bộ mã nguồn vào container
COPY . .

# Cấu hình môi trường cho Nginx/PHP
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_CLI=1
ENV REAL_IP_HEADER=1

# Cài đặt các thư viện PHP phụ thuộc qua Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Phân quyền cho Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Mở cổng 80 cho Nginx
EXPOSE 80

# Chạy script khởi chạy
CMD ["/bin/sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"]