@echo off
echo ========================================
echo  BACKUP SOLUSIPAYMENTMANAGEMENT
echo ========================================
echo.

set backup_dir="D:\BACKUP_SolusiPaymentManagement_%date:~-4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set backup_dir=%backup_dir: =0%

echo Creating backup directory: %backup_dir%
mkdir %backup_dir%

echo.
echo Copying application files...
xcopy "." %backup_dir% /E /H /Y /Q

echo.
echo Copying database...
copy "database\solusipayment.db" %backup_dir%\database\

echo.
echo Creating README file...
echo SolusiPaymentManagement Backup > %backup_dir%\README_BACKUP.txt
echo ================================ >> %backup_dir%\README_BACKUP.txt
echo. >> %backup_dir%\README_BACKUP.txt
echo Backup Date: %date% %time% >> %backup_dir%\README_BACKUP.txt
echo Source: %CD% >> %backup_dir%\README_BACKUP.txt
echo. >> %backup_dir%\README_BACKUP.txt
echo INSTALLATION INSTRUCTIONS: >> %backup_dir%\README_BACKUP.txt
echo -------------------------- >> %backup_dir%\README_BACKUP.txt
echo 1. Extract this backup to your desired location >> %backup_dir%\README_BACKUP.txt
echo 2. Install PHP 7.3+ and SQLite >> %backup_dir%\README_BACKUP.txt
echo 3. Run: php -S localhost:8000 >> %backup_dir%\README_BACKUP.txt
echo 4. Open browser: http://localhost:8000 >> %backup_dir%\README_BACKUP.txt
echo 5. Login with: admin@solusipayment.local / Admin123! >> %backup_dir%\README_BACKUP.txt
echo. >> %backup_dir%\README_BACKUP.txt
echo FEATURES: >> %backup_dir%\README_BACKUP.txt
echo --------- >> %backup_dir%\README_BACKUP.txt
echo - Payment Management System >> %backup_dir%\README_BACKUP.txt
echo - ISP Customer Management >> %backup_dir%\README_BACKUP.txt
echo - Mikrotik Router Integration >> %backup_dir%\README_BACKUP.txt
echo - Invoice & Transaction Management >> %backup_dir%\README_BACKUP.txt
echo - Multi-role Access (Admin/Employee/Customer) >> %backup_dir%\README_BACKUP.txt
echo - CSRF Protection & Security >> %backup_dir%\README_BACKUP.txt

echo.
echo ========================================
echo  BACKUP COMPLETED SUCCESSFULLY!
echo ========================================
echo Backup saved to: %backup_dir%
echo.
pause