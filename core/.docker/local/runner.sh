#!/bin/bash
set -Eeo pipefail
set -o errexit    # Used to exit upon error, avoiding cascading errors

IFS=$'\n\t'

cd /var/www

echo "run on '$DOCKER_ENV' environment!"
echo "run with '$DOCKER_WORKERS' workers!"

if "$ALWAYS_BUILD" == "true"; then
    echo "Running composer install..."

    if [ $DOCKER_ENV == "production" ]; then
        composer install --no-dev --optimize-autoloader
    else
        composer install
    fi
else
    if [ -d vendor ]; then
        echo "vendor ok!"
    else
        composer install
    fi
fi

if "$ALWAYS_MIGRATE" == "true"; then
    echo "Running migrations..."
    php artisan migrate --force
else
    echo "Skipping migrations."
fi

if "$ALWAYS_BUILD" == "true"; then
    echo "Running npm install and build..."
    npm install
    npm build
else
    if [ -d node_modules ]; then
        echo "node_modules ok!"
    else
        npm install
    fi
fi

# run with `octane` if available, otherwise fallback to `artisan serve`
if php artisan | grep -q "octane"; then
    if [ $DOCKER_ENV == "production" ]; then
        php artisan route:clear # somehow if route doesn't being cleared, there's an issue when redeploying with new routes

        php artisan optimize
        php artisan octane:start --host=0.0.0.0 --port=80 --workers=$DOCKER_WORKERS --task-workers=$DOCKER_WORKERS
    else
        php artisan octane:start --host=0.0.0.0 --watch --port=80 --workers=$DOCKER_WORKERS --task-workers=$DOCKER_WORKERS
    fi
else
    if [ $DOCKER_ENV == "production" ]; then
        echo "artisan serve is not running in APP_ENV=production"
        exit 1
    else
        php artisan serve --host=0.0.0.0 --port=80
    fi
fi
