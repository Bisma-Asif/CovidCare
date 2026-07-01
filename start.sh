#!/bin/bash
set -e

PORT=${PORT:-8080}
echo "Starting PHP application on port $PORT..."

# Check if index.php exists
if [ ! -f "/app/index.php" ]; then
    echo "Error: index.php not found"
    exit 1
fi

# Start PHP built-in server
exec php -S 0.0.0.0:$PORT 2>&1
