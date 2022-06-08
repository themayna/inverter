FROM php:8.1-cli as base
RUN apt-get -y update
RUN apt-get -y install git
ENTRYPOINT ["tail", "-f", "/dev/null"]