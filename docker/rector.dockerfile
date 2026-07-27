# export VERSION=2.5.8
# docker build . -f rector.dockerfile --tag budziam/rector:$VERSION
# docker push budziam/rector:$VERSION

FROM php:8.1-cli

RUN apt-get update && \
    apt-get install -y zip unzip git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer global require rector/rector:^2.5
ENV PATH="/root/.composer/vendor/bin:${PATH}"
ENTRYPOINT ["rector"]
