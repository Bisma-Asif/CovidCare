#!/bin/bash
set -e

# Get PORT from environment or use default
if [ -z "$PORT" ]; then
    PORT=8080
fi

echo "🚀 Starting PHP application on 0.0.0.0:$PORT..."

# Check if index.php exists
if [ ! -f "/app/index.php" ]; then
    echo "❌ Error: index.php not found"
    exit 1
fi

echo "✅ index.php found"
echo "📝 Starting PHP built-in server..."

# Start PHP built-in server with proper address binding
exec php -S 0.0.0.0:$PORT -t /app 2>&1
