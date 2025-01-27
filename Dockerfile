FROM php:8.3-cli

# Set the working directory inside the container
WORKDIR /var/www

# Install dependencies including PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libssl-dev \
    unzip \
    git \
    && docker-php-ext-install intl opcache \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean

# Install the composer binary from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the application code to the container
COPY . /var/www

# Set appropriate file permissions for the application directory
RUN chown -R www-data:www-data /var/www

# Expose port 8000 to the outside world
EXPOSE 8000

# Start PHP's built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
