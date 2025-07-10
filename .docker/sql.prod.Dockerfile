FROM mariadb:11.8.2-noble
LABEL maintainer="Pere Orga pere@orga.cat"
LABEL description="MariaDB image used in production."

ARG ARG_MYSQL_ROOT_PWD
ARG ARG_MYSQL_DB
ARG ARG_MYSQL_PWD
ARG ARG_MYSQL_USER

ENV MYSQL_ROOT_PASSWORD=${ARG_MYSQL_ROOT_PWD}
ENV MYSQL_DATABASE=${ARG_MYSQL_DB}
ENV MYSQL_PASSWORD=${ARG_MYSQL_PWD}
ENV MYSQL_USER=${ARG_MYSQL_USER}

COPY ./.docker/mysql /etc/mysql/conf.d

# Set the DB to be read-only (production only)
RUN echo "read_only = 1" >> /etc/mysql/conf.d/custom.cnf && \
    chmod 0444 /etc/mysql/conf.d/*

COPY ./install/db /docker-entrypoint-initdb.d
