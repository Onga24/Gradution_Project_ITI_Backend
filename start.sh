#!/bin/sh

# Exit immediately if a command exits with a non-zero status.
set -e

# Navigate into the main project directory
echo "Navigating into the project directory..."
cd Live-Code-Editor-codeEditor/

# --- Ensure Composer is installed ---
if ! command -v composer >/dev/null 2>&1; then
  echo "Composer not found. Installing..."
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm composer-setup.php
fi

# --- Build Phase ---
echo "Building Laravel backend..."
composer install --no-dev --prefer-dist --optimize-autoloader

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# --- Start Phase ---
echo "Starting Laravel backend..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8080} &

# Keep the script running to prevent the container from exiting
wait -n
