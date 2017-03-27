FROM php:alpine

RUN apk --update add git

RUN php -r "readfile('https://getcomposer.org/download/1.4.1/composer.phar');" > /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

WORKDIR /app
COPY . ./

RUN composer install -o --no-dev --no-interaction --prefer-source

ENV RUNNING_IN_CONTAINER=1

ENTRYPOINT ["php", "scripts/consul-imex.php"]
