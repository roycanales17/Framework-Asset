# Use an official PHP 8.2 image with Apache
FROM php:8.2-apache

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Update and install required dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libbz2-dev libcurl4-nss-dev libxml2-dev libssl-dev libpng-dev libc-client-dev libkrb5-dev libxslt1-dev libzip-dev libonig-dev \
    libmemcached-dev libssh2-1-dev libmcrypt-dev \
    libwebp-dev libjpeg62-turbo-dev libxpm-dev libfreetype6-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-xpm --with-webp --enable-gd \
    && docker-php-ext-install gd

# IMAP extension
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

# Install necessary PHP extensions
RUN docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql bcmath bz2 calendar curl dom exif ftp gettext iconv intl mbstring opcache soap shmop sockets sysvmsg sysvsem sysvshm xsl zip

# Install PECL extensions
RUN pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN pecl install igbinary \
    && docker-php-ext-enable igbinary

# Memcached extension
RUN pecl install memcached --with-libmemcached-dir=/usr \
    && docker-php-ext-enable memcached

# Clean up
RUN docker-php-source delete

# Allow .htaccess overrides
RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/htaccess.conf \
    && echo '    AllowOverride All' >> /etc/apache2/conf-available/htaccess.conf \
    && echo '</Directory>' >> /etc/apache2/conf-available/htaccess.conf \
    && a2enconf htaccess

# Create session storage directory with proper permissions
RUN mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && chmod 700 /var/lib/php/sessions

# Set session save path in PHP config
RUN echo "session.save_path = /var/lib/php/sessions" > /usr/local/etc/php/conf.d/session.ini

# Set the document root to the public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/default-ssl.conf

# Copy your project files into the container's web directory
COPY ./ /var/www/html

# Expose port 80
EXPOSE 80

# Set the working directory (optional)
WORKDIR /var/www/html

# Restart Apache to apply changes
RUN service apache2 restart