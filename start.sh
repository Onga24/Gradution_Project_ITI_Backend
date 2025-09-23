#!/bin/sh

# Exit immediately if a command exits with a non-zero status.
set -e

# --- Build Phase ---

# Navigate to the Laravel backend directory
echo "Building Laravel backend..."
cd Live-Code-Editor-codeEditor/
composer install --no-dev --prefer-dist
php artisan migrate --force

# Navigate back to the root and then to the Node.js directory
echo "Building Node.js server..."
cd ../realtime-server/
npm install

# --- Start Phase ---

# Navigate back to the root to start the services
cd ..

# Start the Laravel backend in the background
echo "Starting Laravel backend..."
php artisan serve --host=0.0.0.0 --port=8000 &

# Start the Node.js server
echo "Starting Node.js server..."
cd realtime-server/
npm start

# Keep the script running to prevent the container from exiting
wait -n
