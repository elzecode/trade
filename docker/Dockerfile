FROM php:8.0-cli
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install bcmath
#CMD ["php", "app.php"]