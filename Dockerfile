FROM php:8.2-apache

# Activer mod_rewrite si un jour tu utilises des routes propres
RUN a2enmod rewrite

# Dossier public = racine du serveur web
WORKDIR /var/www/html

# Copier les fichiers du site
COPY public/ /var/www/html/
COPY src/ /var/www/src/
