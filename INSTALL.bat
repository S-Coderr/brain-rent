@echo off
echo ========================================
echo BrainRent - Automated Setup for XAMPP
echo ========================================
echo.

REM Check if XAMPP is at default location
if not exist "C:\xampp" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP or update this script with your XAMPP path.
    pause
    exit /b 1
)

echo [1/5] Copying project to XAMPP htdocs...
if not exist "C:\xampp\htdocs\brain-rent" mkdir "C:\xampp\htdocs\brain-rent"
xcopy /E /I /Y "." "C:\xampp\htdocs\brain-rent"
echo Done!
echo.

echo [2/5] Opening phpMyAdmin in your browser...
echo [2/5] Creating database using CLI setup script...
cd /d C:\xampp\htdocs\brain-rent
C:\xampp\php\php.exe database\setup_database.php
if errorlevel 1 (
    echo.
    echo ========================================
    echo DATABASE SETUP FAILED
    echo ========================================
    echo Common cause: your XAMPP MariaDB root user has a password set.
    echo Fix options:
        echo  - Edit config\db.local.php (DB_USER / DB_PASSWORD) with your MySQL password
        echo  - OR follow MYSQL_PASSWORD_FIX.md for manual reset steps
    echo.
    pause
)
echo.

echo [3/5] Updating configuration...
echo No config changes required (auto-detects APP_URL; DB creds in config\db.php)
echo Done!
echo.

echo [4/5] Opening BrainRent application...
start http://localhost/brain-rent/pages/index.php
echo.

echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Your BrainRent application should open in your browser.
echo If you see "Database connection failed", make sure:
echo  - MySQL is running in XAMPP
echo  - You imported the SQL file in phpMyAdmin
echo.
echo Application URL: http://localhost/brain-rent/pages/index.php
echo.
pause
