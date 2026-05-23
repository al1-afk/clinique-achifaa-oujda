FROM php:8.3-apache

RUN a2enmod rewrite headers

COPY . /var/www/html/

RUN find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \; \
 && chown -R www-data:www-data /var/www/html

EXPOSE 80
