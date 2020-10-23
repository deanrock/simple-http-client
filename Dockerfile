FROM php:7.4-cli

RUN apt-get update && apt-get install -y git

WORKDIR /usr/src/myapp
COPY composer.json .
COPY composer.lock .

RUN curl -sS  https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && ln -s $(composer config --global home) /root/composer
ENV PATH=$PATH:/root/composer/vendor/bin COMPOSER_ALLOW_SUPERUSER=1

RUN docker-php-ext-configure pcntl
RUN docker-php-ext-install -j$(nproc) pcntl

RUN composer i

COPY main.php .

CMD [ "php", "./main.php" ]
