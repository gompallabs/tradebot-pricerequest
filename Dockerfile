ARG PHP_VERSION=8.2
ARG INSTALL_DIR=app

FROM alpine:3.18 as app-builder
ARG APP_ENV
ARG APP_DIR
ARG INSTALL_DIR

WORKDIR /srv/app

COPY behat.yml.dist composer.json composer.lock symfony.lock .env ./
COPY bin ./bin
COPY config ./config
COPY features ./features
COPY public ./public
COPY src ./src
COPY tests ./tests
COPY translations ./translations

FROM php:${PHP_VERSION}-fpm-alpine AS app_php
ARG GID=1000
ARG UID=1000
ARG TZ
ENV TZ=${TZ}
ENV GID="${GID}"
ENV UID="${UID}"

RUN apk add --no-cache --update linux-headers \
    acl \
    fcgi \
    file \
    gettext \
    icu-dev \
    git \
    rabbitmq-c \
    rabbitmq-c-dev \
    gnu-libiconv \
    libzip-dev \
    libsodium-dev \
    make \
    tzdata \
;

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync

RUN set -o -eux; \
	apk add --no-cache --virtual .build-deps \
		"$PHPIZE_DEPS" \
		zlib-dev \
	; \
	\
	install-php-extensions http intl zip opcache redis amqp \
    ; \
	docker-php-ext-enable \
        amqp \
		opcache \
        intl \
        redis \
        zip \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .phpexts-rundeps $runDeps;


COPY --from=app-builder /srv /srv

WORKDIR /srv/app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY .docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
COPY .docker/php/conf.d/symfony.prod.ini $PHP_INI_DIR/conf.d/symfony.ini
COPY .docker/php/security-checker-install.sh /usr/local/bin/security-checker-install.sh
COPY .docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN ln -s "$PHP_INI_DIR"/php.ini-production "$PHP_INI_DIR"/php.ini ; \
    set -eux; \
    rm -rf var/cache var/log /composer ; \
    mkdir -p var/cache var/log /composer; \
    mkdir -p var/data/download/backup var/data/csv; \
    set -eux; \
	composer install --prefer-dist --no-progress --no-scripts --no-interaction; \
	composer dump-autoload --classmap-authoritative; \
	composer symfony:dump-env dev; \
	composer run-script post-install-cmd; \
	chmod +x bin/console /usr/local/bin/docker-healthcheck /usr/local/bin/docker-entrypoint.sh /usr/local/bin/security-checker-install.sh; \
    cp /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && apk del tzdata; \
    sync

VOLUME /srv/app/var

ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["php-fpm"]