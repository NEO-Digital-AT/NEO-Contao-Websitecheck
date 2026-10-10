FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libwebp-dev libavif-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install -j"$(nproc)" gd intl pdo_mysql zip opcache \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && printf 'memory_limit=512M\nupload_max_filesize=32M\npost_max_size=32M\nmax_execution_time=120\n' > /usr/local/etc/php/conf.d/contao.ini \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY entrypoint.sh /usr/local/bin/neo-entrypoint
COPY seed.php /usr/local/share/neo-seed.php
RUN chmod +x /usr/local/bin/neo-entrypoint

WORKDIR /var/www/html
ENTRYPOINT ["neo-entrypoint"]
CMD ["apache2-foreground"]
