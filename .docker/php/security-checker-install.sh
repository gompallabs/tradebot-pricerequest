# Install PHP Security Checker
PHP_SC_VERSION=$(curl -s "https://api.github.com/repos/fabpot/local-php-security-checker/releases/latest" |  grep '"tag_name":' |   sed -E 's/.*"([^"]+)".*/\1/;s/^v//')
[ ! -f ./local-php-security-checker ] && echo 'Downloading local security checker '${PHP_SC_VERSION} && curl -LSs https://github.com/fabpot/local-php-security-checker/releases/download/v${PHP_SC_VERSION}/local-php-security-checker_${PHP_SC_VERSION}_linux_amd64 >./local-php-security-checker
mv ./local-php-security-checker /usr/local/bin/local-php-security-checker
chmod +x /usr/local/bin/local-php-security-checker
unset PHP_SC_VERSION
