# Igual à hospedagem compartilhada: a raiz do site é a pasta do projeto
# e o .htaccess da raiz serve tudo de public/.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers
