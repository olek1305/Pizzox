FROM php:8.3-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libssl-dev \
    && docker-php-ext-install intl opcache \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www

RUN chown -R www-data:www-data /var/www

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
