#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

mkdir -p /srv/app/var/cache /srv/app/var/log
mkdir -p /composer
setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX /srv/app/var/cache
setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX /srv/app/var/cache
setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX /srv/app/var/log
setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX /srv/app/var/log

# The first time volumes are mounted, the vendors needs to be reinstalled
if [ ! -d vendor/ ]; then
    composer install --prefer-dist --no-progress --no-interaction --no-dev
fi

if [ "$APP_ENV" = 'dev' ]; then
  setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX /composer
  setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX /composer
  chown -R "${UID}":"${GID}" /srv/app /composer /home/"$USER"
  composer install --prefer-dist --no-progress --no-interaction
fi

exec docker-php-entrypoint "$@"

