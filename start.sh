#!/bin/sh

# Exit immediately if a command exits with a non-zero status.
set -e

# Navigate into the main project directory
echo "Navigating into the project directory..."
cd Live-Code-Editor-codeEditor/

# --- Build Phase ---

# Build and install dependencies for the Laravel backend
echo "Building Laravel backend..."
composer install --no-dev --prefer-dist
php artisan migrate --force



# --- Start Phase ---


echo "Starting Laravel backend..."
php artisan serve --host=0.0.0.0 --port=$PORT &



# Keep the script running to prevent the container from exiting
wait -n
