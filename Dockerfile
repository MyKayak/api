FROM php:8.2-apache

# 1. Install the MySQL database driver
RUN docker-php-ext-install pdo pdo_mysql

# 2. Enable the "rewrite" module so .htaccess works
RUN a2enmod rewrite

# 3. Tell Apache it is ALLOWED to listen to your .htaccess file
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 4. Set up your files
WORKDIR /var/www
COPY src/ /var/www/html/
COPY scripts/ /var/www/scripts/

# 5. Create a "bridge" so reset.php can find your code at ../src/
RUN ln -s /var/www/html /var/www/src

# 6. Give Apache permission to read and run everything
RUN chown -R www-data:www-data /var/www
