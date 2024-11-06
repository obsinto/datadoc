# Usa a imagem base do PHP
FROM php:8.2-fpm

# Define argumentos para cache busting e configuração
ARG DEBIAN_FRONTEND=noninteractive

# Instala dependências necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    # Instalar Node.js e npm
    && curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Definir o diretório de trabalho
WORKDIR /var/www

# Copiar os arquivos do aplicativo para o contêiner
COPY . .

# Instalar as dependências do Laravel no diretório correto
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Instalar dependências do npm e construir o Vite
RUN npm install && npm run build

# Gerar a chave de aplicativo do Laravel
RUN php artisan key:generate --no-interaction

# Definir permissões nos diretórios corretos
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R ug+rwx /var/www/storage /var/www/bootstrap/cache

# Garantir que o log de Laravel exista e tenha permissões corretas
RUN touch /var/www/storage/logs/laravel.log \
    && chown -R www-data:www-data /var/www/storage/logs \
    && chmod -R 775 /var/www/storage/logs

# Copiar o entrypoint.sh para o contêiner
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Tornar o entrypoint.sh executável
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expor a porta 9000 para o PHP-FPM
EXPOSE 9000

# Usar o script de entrada para iniciar o contêiner
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
