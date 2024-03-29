# export VERSION=0.12.23
# docker build . -f rector.dockerfile --tag budziam/rector:$VERSION
# docker push budziam/rector:$VERSION

FROM php:8.1

RUN apt-get update && \
    apt-get install -y zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer global require rector/rector
ENTRYPOINT ["/root/.composer/vendor/bin/rector"]
