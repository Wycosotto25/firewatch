#!/bin/bash
set -e

# Railway provides $PORT at runtime; default to 80 if not set (local testing)
PORT=${PORT:-80}

# Rewrite Apache's listen port and virtual host to match Railway's assigned port
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

exec apache2-foreground