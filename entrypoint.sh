#!/bin/sh

# Definir permissões nos diretórios corretos antes de qualquer coisa
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Gerar a chave de aplicativo do Laravel, se não existir
if [ ! -f .env ]; then
    echo ".env file not found!"
    exit 1
fi

if ! grep -q 'APP_KEY' .env; then
    echo "APP_KEY not found, generating..."
    php artisan key:generate --force
fi

# Rodar as migrations com --force
php artisan migrate --force

# Limpar caches antigos
php artisan cache:clear

# Cachear as configurações atuais
php artisan config:cache

# Iniciar PHP-FPM em primeiro plano
exec php-fpm
