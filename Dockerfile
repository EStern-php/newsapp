FROM php:8.2-apache
WORKDIR /var/www/html

# Apache rewrite för snygga URL:er
RUN a2enmod rewrite

# PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql