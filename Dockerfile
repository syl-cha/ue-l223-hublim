FROM php:8.4-apache

# 1. Installation des dépendances système (on retire libpq-dev qui était pour PostgreSQL)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# 2. Installation et activation des extensions PHP (pdo_mysql au lieu de pdo_pgsql)
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl opcache

# 3. Modification du DocumentRoot d'Apache pour pointer vers le dossier /public de Symfony 8
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Activation du module de réécriture d'URL d'Apache (Requis par Symfony)
RUN a2enmod rewrite

# 5. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du dossier de travail
WORKDIR /var/www/html