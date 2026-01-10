ARG PHP_VERSION=8.3

FROM ghcr.io/digital-entropy/dokar-php/octane:${PHP_VERSION}

# opcache & xdebug
RUN apt-get update \
    && apt-get install -y php8.3-opcache php8.3-xdebug

# cleanup
RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY ./runner.sh /runner.sh
COPY ./conf.d/20-opcache.ini /etc/php/8.3/cli/conf.d/20-opcache.ini

CMD ["/runner.sh"]
