<?php
/**
 * Database Configuration for Mahadev Tent House - XAMPP Local Setup
 */

// XAMPP Database Configuration
define('DB_HOST', 'localhost');           // Always localhost for XAMPP
define('DB_NAME', 'mahadev_tent_house');  // Your database name
define('DB_USER', 'root');                // Default XAMPP username
define('DB_PASS', '');                    // Default XAMPP password (empty)

// Site Configuration
define('SITE_URL', 'http://localhost/mahadev_tent'); // Update with your folder name
define('SITE_NAME', 'Mahadev Tent House');

// Email Configuration for Testimonial Notifications (Optional)
define('NOTIFICATION_EMAIL', 'kavitaprj2006@gmail.com'); // Email for testimonial notifications
define('FROM_EMAIL', 'noreply@localhost'); // From email address
define('FROM_NAME', 'Mahadev Tent House');

// Security Settings
define('ENABLE_RATE_LIMITING', false); // Disable for local testing
define('MAX_SUBMISSIONS_PER_HOUR', 5); // Max testimonial submissions per IP per hour

// Error Reporting (enabled for local development)
define('DEBUG_MODE', true); // Keep true for XAMPP testing

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Test database connection
try {
    $test_connection = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Connection successful
    if (DEBUG_MODE) {
        error_log("✅ Database connection successful!");
    }
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        error_log("❌ Database connection failed: " . $e->getMessage());
        echo "<div style='background:red;color:white;padding:10px;'>Database Error: " . $e->getMessage() . "</div>";
    }
}
?>