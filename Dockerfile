FROM richarvey/nginx-php-fpm:latest

WORKDIR /var/www/html

COPY . .

ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_CLI=1
ENV REAL_IP_HEADER=1

# Cài đặt extension và chạy composer install với tham số an toàn
RUN apk add --no-cache php83-pdo_pgsql php83-pgsql \
    && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/bin/sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"]