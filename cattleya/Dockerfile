FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        zip

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html


RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/app/public\n\
    <Directory /var/www/html/app/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf


EXPOSE 80