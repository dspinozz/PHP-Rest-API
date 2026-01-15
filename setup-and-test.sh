#!/bin/bash

# Setup and Test Script for PHP REST API Framework
# This script installs dependencies and runs curl tests

set -e

echo "=========================================="
echo "PHP REST API Framework - Setup & Test"
echo "=========================================="

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed or not in PATH"
    echo "Please install PHP 8.2+ to continue"
    exit 1
fi

echo "✅ PHP found: $(php --version | head -1)"
echo ""

# Find an open port
find_open_port() {
    for port in 9000 9001 9002 9003 9004 9005; do
        if ! (echo >/dev/tcp/localhost/$port) 2>/dev/null; then
            echo $port
            return 0
        fi
    done
    echo "9000"
}

PORT=$(find_open_port)
echo "Using port: $PORT"
echo ""

# Install Composer if needed
if [ ! -f composer.phar ] && ! command -v composer &> /dev/null; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    COMPOSER_CMD="php composer.phar"
else
    if [ -f composer.phar ]; then
        COMPOSER_CMD="php composer.phar"
    else
        COMPOSER_CMD="composer"
    fi
fi

# Install dependencies
if [ ! -d vendor ]; then
    echo "📦 Installing PHP dependencies..."
    $COMPOSER_CMD install --no-interaction
    echo "✅ Dependencies installed"
else
    echo "✅ Dependencies already installed"
fi
echo ""

# Start server in background
echo "🚀 Starting PHP server on port $PORT..."
php -S localhost:$PORT test-server.php > /tmp/php-server.log 2>&1 &
SERVER_PID=$!

# Wait for server to start
sleep 2

# Check if server is running
if ! kill -0 $SERVER_PID 2>/dev/null; then
    echo "❌ Server failed to start. Check /tmp/php-server.log"
    exit 1
fi

echo "✅ Server started (PID: $SERVER_PID)"
echo ""

# Run curl tests
echo "🧪 Running curl tests..."
echo ""
./test-curl.sh "http://localhost:$PORT"

# Capture test result
TEST_RESULT=$?

# Stop server
echo ""
echo "🛑 Stopping server..."
kill $SERVER_PID 2>/dev/null || true
wait $SERVER_PID 2>/dev/null || true

echo ""
if [ $TEST_RESULT -eq 0 ]; then
    echo "✅ All tests passed!"
    exit 0
else
    echo "❌ Some tests failed"
    exit 1
fi
