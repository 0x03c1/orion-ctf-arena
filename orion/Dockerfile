# Órion Corp — alvo de treino ORIGINAL (vulnerabilidades modernas). Não é DVWA.
FROM php:8.2-apache

# Fuso de Recife + extensão SQLite + mod_rewrite (rotas da API)
RUN ln -sf /usr/share/zoneinfo/America/Recife /etc/localtime \
 && echo "America/Recife" > /etc/timezone \
 && apt-get update \
 && apt-get install -y --no-install-recommends libsqlite3-dev pkg-config \
 && docker-php-ext-install pdo_sqlite \
 && a2enmod rewrite headers \
 && apt-get purge -y pkg-config && apt-get autoremove -y \
 && rm -rf /var/lib/apt/lists/*

# Permitir .htaccess (rewrite) e repassar o header Authorization ao PHP
RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '  AllowOverride All' \
    '  Require all granted' \
    '</Directory>' \
    'SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1' \
    > /etc/apache2/conf-enabled/orion.conf

# Log no formato do placar: gateway|IP real(CF)|data|request|status|Location|user-agent
RUN printf '%s\n' \
    'LogFormat "%a|%{CF-Connecting-IP}i|%t|%r|%>s|%{Location}o|%{User-Agent}i" ctf' \
    >> /etc/apache2/apache2.conf \
 && sed -ri 's#CustomLog \$\{APACHE_LOG_DIR\}/access.log combined#CustomLog /proc/self/fd/1 ctf#' \
    /etc/apache2/sites-available/000-default.conf

COPY app/ /var/www/html/
COPY assets/ /var/www/html/assets/
RUN mkdir -p /var/www/data && chown -R www-data:www-data /var/www/data

EXPOSE 80
