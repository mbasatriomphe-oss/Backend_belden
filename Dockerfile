FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

EXPOSE 10000

CMD ["sh", "-c", "if [ \"${DB_CONNECTION:-}\" != \"mysql\" ]; then echo 'ERROR: DB_CONNECTION must be mysql on Render'; exit 1; fi && for required_var in DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do eval \"value=\\${$required_var:-}\"; if [ -z \"$value\" ]; then echo \"ERROR: missing Render variable: $required_var\"; exit 1; fi; done && mkdir -p storage/certs && if [ -n \"${CA_PEM_BASE64:-}\" ]; then echo \"$CA_PEM_BASE64\" | base64 -d > storage/certs/ca.pem && chmod 644 storage/certs/ca.pem; fi && PORT=\"${PORT:-10000}\" && sed -ri \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -ri \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/*.conf && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && php artisan config:clear && php artisan migrate --force && exec apache2-foreground"]