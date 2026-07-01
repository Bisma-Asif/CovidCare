FROM php:8.1-cli

WORKDIR /app

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy application files
COPY . .

# Expose port
EXPOSE 8080

# Start PHP server
CMD php -S 0.0.0.0:${PORT:-8080}
