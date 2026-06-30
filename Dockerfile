FROM php:8.2-apache

# Install mysqli (and pdo_mysql, commonly needed alongside it)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache's mod_rewrite (useful for clean URLs / routing)
RUN a2enmod rewrite

# Fix "More than one MPM loaded" — mod_php requires the prefork MPM,
# so disable event/worker and enable prefork explicitly
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork

# Copy your app code into the container's web root
COPY . /var/www/html/

# Copy and prepare the startup script that binds Apache to Railway's $PORT
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
