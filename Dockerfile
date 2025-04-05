FROM php:8.3-cli

# Set the working directory inside the container
WORKDIR /var/www

# Install dependencies including PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libssl-dev \
    unzip \
    git \
    && pecl install mongodb-1.21.0 \
    && pecl install redis \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-enable redis \
    && docker-php-ext-install intl opcache \
    && apt-get clean

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]


# Install the composer binary from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest

# Copy the application code to the container
COPY . /var/www

# Set appropriate file permissions for the application directory
RUN chown -R www-data:www-data /var/www

# Expose port 8000 to the outside world
EXPOSE 8000

# Start PHP's built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
