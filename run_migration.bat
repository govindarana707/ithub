@echo off
echo =====================================================
echo Database Migration Execution
echo =====================================================

echo.
echo Step 1: Creating backup...
"C:\xampp\mysql\bin\mysqldump.exe" -u root -p it_hub_new > backup_before_migration.sql
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to create backup
    pause
    exit /b 1
)
echo ✅ Backup created successfully

echo.
echo Step 2: Running migration...
"C:\xampp\mysql\bin\mysql.exe" -u root -p it_hub_new < database\migrations\002_migrate_and_cleanup.sql
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Migration failed - check error messages above
    echo.
    echo Step 3: Running emergency rollback...
    "C:\xampp\mysql\bin\mysql.exe" -u root -p it_hub_new < database\migrations\emergency_rollback.sql
    if %ERRORLEVEL% NEQ 0 (
        echo ERROR: Rollback also failed! Check backup file.
        echo You may need to restore manually from backup_before_migration.sql
    ) else (
        echo ✅ Emergency rollback completed
    )
) else (
    echo ✅ Migration completed successfully!
)

echo.
echo Step 4: Verification...
"C:\xampp\mysql\bin\mysql.exe" -u root -p it_hub_new -e "SELECT 'Migration Status:', COUNT(*) as completed_steps FROM migration_log WHERE status = 'completed';"
"C:\xampp\mysql\bin\mysql.exe" -u root -p it_hub_new -e "SELECT 'Failed Steps:', COUNT(*) as failed_steps FROM migration_log WHERE status = 'failed';"
"C:\xampp\mysql\bin\mysql.exe" -u root -p it_hub_new -e "SELECT 'Total Tables:', COUNT(*) as total FROM information_schema.tables WHERE table_schema = 'it_hub_new';"

echo.
echo =====================================================
echo Migration complete! Check the results above.
echo =====================================================
pause
