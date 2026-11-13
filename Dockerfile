FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
 && a2enmod rewrite \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

COPY . /var/www/html
RUN sed -ri "s!AllowOverride None!AllowOverride All!g" /etc/apache2/apache2.conf \
 && chown -R www-data:www-data /var/www/html/database
