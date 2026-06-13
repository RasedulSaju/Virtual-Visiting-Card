@echo off
echo Installing PHP dependencies for Virtual Visiting Card...
echo.

REM Check if Composer is installed
where composer >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo Composer not found. Downloading Composer installer...
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=. --filename=composer.phar
    php composer.phar install --no-dev
    del composer-setup.php
) ELSE (
    echo Composer found. Running install...
    composer install --no-dev
)

echo.
echo Done! PHPMailer installed in vendor/
echo You can now configure SMTP in Admin - Settings - SMTP
pause
