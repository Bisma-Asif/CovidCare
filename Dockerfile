FROM php:8.1-cli

WORKDIR /app

# Install required PHP extensions and utilities
RUN apt-get update && \
    apt-get install -y default-mysql-client && \
    docker-php-ext-install mysqli pdo pdo_mysql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Copy startup script
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

# Expose port
EXPOSE 8080

# Start PHP server
CMD ["bash", "start.sh"]
