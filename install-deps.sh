#!/bin/bash

# Install PHP Dependencies Script
# Tries multiple methods to install dependencies

set -e

echo "=========================================="
echo "Installing PHP Dependencies"
echo "=========================================="

cd "$(dirname "$0")"

# Method 1: Check for PHP directly
if command -v php &> /dev/null; then
    PHP_CMD="php"
    echo "✅ Found PHP: $(php --version | head -1)"
elif [ -f /usr/bin/php ]; then
    PHP_CMD="/usr/bin/php"
    echo "✅ Found PHP at /usr/bin/php"
else
    echo "❌ PHP not found. Trying alternative methods..."
    
    # Method 2: Try Docker
    if command -v docker &> /dev/null; then
        echo "🐳 Using Docker to install dependencies..."
        docker run --rm -v "$(pwd):/app" -w /app composer:latest install --no-interaction
        echo "✅ Dependencies installed via Docker"
        exit 0
    fi
    
    # Method 3: Download vendor files directly (not recommended but possible)
    echo "⚠️  PHP not available. Cannot install dependencies."
    echo ""
    echo "Please install PHP 8.2+ and Composer, then run:"
    echo "  composer install"
    echo ""
    echo "Or use Docker:"
    echo "  docker run --rm -v \$(pwd):/app -w /app composer:latest install"
    exit 1
fi

# Install Composer if needed
if [ ! -f composer.phar ] && ! command -v composer &> /dev/null; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | $PHP_CMD
    COMPOSER_CMD="$PHP_CMD composer.phar"
else
    if [ -f composer.phar ]; then
        COMPOSER_CMD="$PHP_CMD composer.phar"
    else
        COMPOSER_CMD="composer"
    fi
fi

# Install dependencies
echo "📦 Installing PHP dependencies..."
$COMPOSER_CMD install --no-interaction

echo ""
echo "✅ Dependencies installed successfully!"
echo ""
echo "To start the test server:"
echo "  $PHP_CMD -S localhost:9000 test-server.php"
echo ""
echo "To run tests:"
echo "  ./test-curl.sh"
