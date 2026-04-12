FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www
COPY src/ /var/www/src/
COPY scripts/ /var/www/scripts/
RUN rm -rf html && ln -s src html
RUN chown -R www-data:www-data /var/www