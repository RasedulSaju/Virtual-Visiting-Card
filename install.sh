#!/bin/bash
# ============================================================
# install.sh — PHPMailer installer for Linux / cPanel SSH
#
# USAGE (via SSH or cPanel Terminal):
#   cd ~/public_html/vvcard
#   bash install.sh
# ============================================================

echo ""
echo "Virtual Visiting Card — PHPMailer Installer"
echo "============================================"
echo ""

# Check if already installed
if [ -f "vendor/phpmailer/phpmailer/src/PHPMailer.php" ]; then
    echo "PHPMailer is already installed."
    echo "Location: vendor/phpmailer/phpmailer/src/"
    exit 0
fi

# Try system Composer first
if command -v composer &>/dev/null; then
    echo "Composer found. Running: composer install --no-dev"
    composer install --no-dev
    echo ""
    echo "Done! PHPMailer installed in vendor/"
    exit 0
fi

# Try local composer.phar
if [ -f "composer.phar" ]; then
    echo "composer.phar found. Running install..."
    php composer.phar install --no-dev
    echo ""
    echo "Done! PHPMailer installed in vendor/"
    exit 0
fi

# Download Composer on the fly
echo "Composer not found. Downloading from getcomposer.org..."
echo ""

if command -v curl &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
elif command -v wget &>/dev/null; then
    wget -q -O - https://getcomposer.org/installer | php
else
    echo "ERROR: Neither curl nor wget found."
    echo "Please install PHPMailer manually (see README.md)."
    exit 1
fi

echo ""
echo "Running: php composer.phar install --no-dev"
php composer.phar install --no-dev

echo ""
echo "Done! PHPMailer installed in vendor/"
echo ""
echo "You can delete composer.phar if you no longer need it:"
echo "  rm composer.phar"
