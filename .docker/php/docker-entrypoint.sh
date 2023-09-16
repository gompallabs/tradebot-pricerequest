#!/bin/sh
set -e
UID=1000
setfacl -R -m u:www-data:rwX -m u:"${UID}":rwX /srv/app/var/cache
setfacl -dR -m u:www-data:rwX -m u:"${UID}":rwX /srv/app/var/cache
setfacl -R -m u:www-data:rwX -m u:"${UID}":rwX /srv/app/var/log
setfacl -dR -m u:www-data:rwX -m u:"${UID}":rwX /srv/app/var/log
setfacl -R -m u:www-data:rwX -m u:"${UID}":rwX /srv/app/var/data/download/backup
setfacl -dR -m u:www-data:rwX -m u:"${UID}":rwX /srv/app/var/data/download/backup
setfacl -R -m u:www-data:rwX -m u:"${UID}":rwX /composer
setfacl -dR -m u:www-data:rwX -m u:"${UID}":rwX /composer

composer install --prefer-dist --no-progress --no-interaction

echo "build ok"
/usr/local/bin/security-checker-install.sh

exec docker-php-entrypoint "$@"



