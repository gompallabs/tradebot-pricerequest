ARG PHP_VERSION=8.2
ARG INSTALL_DIR=app

FROM alpine as app-builder
ARG APP_ENV
ARG APP_DIR
ARG INSTALL_DIR

WORKDIR /srv/${INSTALL_DIR}

COPY ${APP_DIR}/composer.json ${APP_DIR}/composer.lock ${APP_DIR}/symfony.lock ${APP_DIR}/.env ./
COPY ${APP_DIR}/bin ./bin
COPY ${APP_DIR}/config ./config
COPY ${APP_DIR}/public ./public
COPY ${APP_DIR}/src ./src
COPY ${APP_DIR}/tests ./tests
COPY ${APP_DIR}/translations ./translations
COPY ${APP_DIR}/features ./features

FROM php:${PHP_VERSION}-fpm-alpine AS app_php
ARG PHP_CONF_DIR
ARG INSTALL_DIR
ARG ENTRYPOINT_FILE
ARG GID=1000
ARG UID=1000
ARG TZ
ENV TZ=${TZ}
ENV GID="${GID}"
ENV UID="${UID}"

# persistent / runtime deps
RUN apk add --no-cache --update linux-headers \
		acl \
		fcgi \
		file \
		gettext \
        icu-dev \
		git \
		gnu-libiconv \
        libzip-dev \
        libsodium-dev \
        make \
        tzdata \
	;

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions http

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		zlib-dev \
	; \
	\
	docker-php-ext-configure zip; \
	docker-php-ext-install -j$(nproc) \
		intl \
		zip \
        opcache \
	; \
    pecl install \
        redis \
        xdebug \
    ; \
    pecl clear-cache; \
	docker-php-ext-enable \
		opcache \
        intl \
        redis \
        xdebug \
        zip \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]
COPY --from=app-builder /srv /srv

WORKDIR /srv/${INSTALL_DIR}

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ${PHP_CONF_DIR}/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
COPY ${PHP_CONF_DIR}/conf.d/symfony.prod.ini $PHP_INI_DIR/conf.d/symfony.ini
COPY ${PHP_CONF_DIR}/${ENTRYPOINT_FILE} /usr/local/bin/docker-entrypoint.sh

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini ; \
    set -eux; \
	mkdir -p var/cache var/log; \
    mkdir -p var/data/download var/data/csv; \
	composer install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer symfony:dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console /usr/local/bin/docker-healthcheck /usr/local/bin/docker-entrypoint.sh; \
    cp /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && apk del tzdata; \
    sync

VOLUME /srv/app/var

ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["php-fpm"]