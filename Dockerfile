FROM php:7.2-fpm-alpine
ARG COMPOSER_VERSION=1.8.3
ARG composerToken
ARG npmToken
ENV NPM_TOKEN=$npmToken

RUN mkdir /app
WORKDIR /app

# php modules
RUN apk add --no-cache --virtual .build-deps autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c libxml2-dev zlib-dev git bash nano less unzip nodejs nodejs-npm \
    && docker-php-ext-install opcache pdo_mysql mysqli soap zip dom xml && \
    npm install -g gulp

# install composer
RUN curl -L https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --version=${COMPOSER_VERSION} --install-dir=/usr/local/bin --filename=composer \
    && rm -f composer-setup.php

COPY package.json /app
COPY . /app
RUN composer config --global --auth http-basic.repo.packagist.com token ${composerToken}
RUN npm install && \
    composer install && \
    gulp build
CMD bash
