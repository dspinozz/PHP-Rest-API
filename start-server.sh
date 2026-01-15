#!/bin/bash
cd /Users/david/php-rest-api-framework

# Clean up old containers
docker stop php-api 2>/dev/null
docker rm php-api 2>/dev/null

# Start server
echo "Starting PHP server..."
docker run -d --name php-api \
  -v "$(pwd):/app" \
  -w /app \
  -p 9000:9000 \
  php:8.2-cli \
  php -S 0.0.0.0:9000 test-server.php

# Wait for server to start
sleep 2

# Verify it's running
if docker ps | grep -q php-api; then
    echo "✅ Server started successfully!"
    echo ""
    echo "Testing health check..."
    curl -s http://localhost:9000/ | python3 -m json.tool 2>/dev/null || curl -s http://localhost:9000/
    echo ""
    echo ""
    echo "Server is running on http://localhost:9000"
    echo "To stop: docker stop php-api && docker rm php-api"
else
    echo "❌ Server failed to start. Check logs:"
    docker logs php-api
fi
