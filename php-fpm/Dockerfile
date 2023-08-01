FROM php:8.1-fpm

RUN apt-get update && apt-get install -y openssl git zip unzip

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && composer -V

# Set timezone
ARG TZ
RUN if [$TZ != ""]; then ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', $TZ  > /usr/local/etc/php/conf.d/tzone.ini && date; fi

# Type docker-php-ext-install to see available extensions
RUN docker-php-ext-install pdo pdo_mysql bcmath sockets opcache

COPY php-fpm.d/* /usr/local/etc/php-fpm.d/
COPY conf.d/* /usr/local/etc/php/conf.d/

RUN echo 'alias sf=/var/www/symfony/bin/console' >> ~/.bashrc

RUN chown www-data:1000 -R /var/www/ && usermod -u 1000 www-data
USER www-data

WORKDIR /var/www/symfony
