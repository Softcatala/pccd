FROM mariadb:12.3.2-noble
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="MariaDB image used in production."

ARG ARG_MYSQL_PWD

ENV MYSQL_DATABASE=pccd
ENV MYSQL_USER=pccd
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_RANDOM_ROOT_PASSWORD=yes

COPY ./.docker/mysql /etc/mysql/conf.d

# Set the DB to be read-only (production only)
RUN echo "read_only = 1" >>/etc/mysql/conf.d/custom.cnf \
  && chmod 0444 /etc/mysql/conf.d/*

COPY ./install/db /docker-entrypoint-initdb.d
