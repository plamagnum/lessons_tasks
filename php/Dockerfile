# Базовий образ PHP 8.2 з підтримкою FastCGI Process Manager
FROM php:8.2-fpm

# Встановлення необхідних системних пакетів
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Встановлення PHP розширень
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring

# Робоча директорія
WORKDIR /var/www/html

# Налаштування прав доступу
RUN chown -R www-data:www-data /var/www/html

# Порт PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
