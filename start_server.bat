@echo off
echo Starting School Management System...
echo.

REM Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo Error: PHP is not found in PATH
    echo Please make sure XAMPP PHP is in your system PATH
    echo Or run this from XAMPP shell
    pause
    exit /b 1
)

REM Check if public directory exists
if not exist "public" (
    echo Error: public directory not found
    echo Make sure you're running this from the Laravel project root
    pause
    exit /b 1
)

echo Starting PHP development server...
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
echo.

REM Start the server with error suppression
php -d error_reporting="E_ALL & ~E_DEPRECATED" -S localhost:8000 -t public

pause
