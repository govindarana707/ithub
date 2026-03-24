<?php
/**
 * Trial Expiration Processing Cron Job
 * 
 * This script should be run daily to process trial expirations,
 * send reminder notifications, and update trial statuses.
 * 
 * Usage: 
 * - Add to cron job: 0 2 * * * /usr/bin/php /path/to/process_trial_expirations.php
 * - Or run manually: php process_trial_expirations.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/TrialService.php';
require_once __DIR__ . '/../services/NotificationService.php';

// Initialize services
$trialService = new TrialService();
$notificationService = new NotificationService();

echo "=== Trial Expiration Processing Started ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Process trial expirations
    echo "Processing trial expirations...\n";
    $result = $trialService->processTrialExpirations();
    
    echo "✓ Processed {$result['processed']} expired trials\n";
    echo "✓ Sent {$result['notifications_sent']} notifications\n";
    
    // Clean old notifications (older than 30 days)
    echo "\nCleaning old notifications...\n";
    $cleanedNotifications = $notificationService->cleanOldNotifications(30);
    echo "✓ Cleaned {$cleanedNotifications} old notifications\n";
    
    // Get trial statistics for logging
    echo "\nCurrent trial statistics:\n";
    $stats = $trialService->getTrialStatistics();
    
    echo "├── Total trials: {$stats['total_trials']}\n";
    echo "├── Active trials: {$stats['active_trials']}\n";
    echo "├── Expired trials: {$stats['expired_trials']}\n";
    echo "├── Completed trials: {$stats['completed_trials']}\n";
    echo "├── Converted trials: {$stats['converted_trials']}\n";
    echo "└── Conversion rate: {$stats['conversion_rate']}%\n";
    
    echo "\n=== Trial Expiration Processing Completed ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
