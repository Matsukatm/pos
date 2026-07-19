@echo off
REM Navigate to your Laravel project folder
cd /d C:\Blair2004-NexoPOS-bc622f6

:loop
REM Start Laravel development server
php artisan serve --host=0.0.0.0 --port=8080

REM Wait 5 seconds before restarting if it stops
timeout /t 5

REM Loop again
goto loop