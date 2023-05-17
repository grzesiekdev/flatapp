FROM php:8.2-fpm

RUN apt-get update \
    && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip bash libgconf-2-4 libatk1.0-0 libatk-bridge2.0-0 libgdk-pixbuf2.0-0 libgtk-3-0 libgbm-dev libnss3-dev libxss-dev wget bzip2 libxtst6 libgtk-3-0 libx11-xcb-dev libdbus-glib-1-2 libxt6 libpci-dev libasound2 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

WORKDIR /var/www/docker

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ARG GECKODRIVER_VERSION=0.28.0
ARG FIREFOX_VERSION=113.0.1
RUN wget -q https://github.com/mozilla/geckodriver/releases/download/v$GECKODRIVER_VERSION/geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz; \
    tar -zxf geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz -C /usr/bin; \
    rm geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz
RUN wget 'https://download.mozilla.org/?product=firefox-latest&os=linux64&lang=en-US' -O firefox-$FIREFOX_VERSION.tar.bz2; \
    tar xjf firefox-$FIREFOX_VERSION.tar.bz2; \
    mv firefox /opt; \
    ln -s /opt/firefox/firefox /usr/local/bin/firefox; \
    rm firefox-$FIREFOX_VERSION.tar.bz2;

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

RUN git config --global user.email "grzesiekb@onet.pl" \
    && git config --global user.name "grzesiekdev"
