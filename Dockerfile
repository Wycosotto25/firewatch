FROM php:8.2-apache

# Install mysqli (and pdo_mysql, commonly needed alongside it)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache's mod_rewrite (useful for clean URLs / routing)
RUN a2enmod rewrite

# Fix "More than one MPM loaded" — mod_php requires the prefork MPM.
# a2dismod/a2enmod can no-op silently on this base image, so remove the
# conflicting MPM symlinks directly and ensure only prefork remains.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Copy your app code into the container's web root
COPY . /var/www/html/

# Copy and prepare the startup script that binds Apache to Railway's $PORT
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
