<?php
/**
 * Save Review Endpoint
 * Saves new testimonials to database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Database connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Create testimonials table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        message TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createTableSQL);
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $email = trim($_POST['email'] ?? '') ?: null;
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must be less than 100 characters';
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long';
    } elseif (strlen($message) > 1000) {
        $errors[] = 'Message must be less than 1000 characters';
    }
    
    // Spam detection
    $spamWords = ['viagra', 'casino', 'poker', 'loan', 'debt', 'free money', 'click here', 'buy now'];
    $textToCheck = strtolower($name . ' ' . $message);
    
    foreach ($spamWords as $word) {
        if (strpos($textToCheck, $word) !== false) {
            $errors[] = 'Your message contains inappropriate content';
            break;
        }
    }
    
    // Check for excessive links
    if (preg_match_all('/http[s]?:\/\//', $textToCheck) > 2) {
        $errors[] = 'Too many links in message';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors]);
        exit;
    }
    
    // Get client IP
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $clientIP = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $clientIP = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $clientIP = trim($ips[0]);
    }
    
    // Check for duplicate submissions (same IP within 1 hour)
    if (ENABLE_RATE_LIMITING) {
        $rateLimitSQL = "SELECT COUNT(*) FROM testimonials 
                        WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $rateLimitStmt = $pdo->prepare($rateLimitSQL);
        $rateLimitStmt->execute([$clientIP]);
        
        if ($rateLimitStmt->fetchColumn() >= MAX_SUBMISSIONS_PER_HOUR) {
            http_response_code(429);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Too many submissions. Please wait before submitting another review.'
            ]);
            exit;
        }
    }
    
    // Insert testimonial
    $insertSQL = "INSERT INTO testimonials (name, email, rating, message, ip_address) 
                  VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($insertSQL);
    $stmt->execute([$name, $email, $rating, $message, $clientIP]);
    
    $testimonialId = $pdo->lastInsertId();
    
    // Send notification email to admin (optional)
    if (defined('NOTIFICATION_EMAIL') && !empty(NOTIFICATION_EMAIL)) {
        $subject = "New Testimonial Submitted - " . SITE_NAME;
        $emailMessage = "
New testimonial submitted on your website:

⭐ Rating: $rating/5 stars
👤 Name: $name
📧 Email: " . ($email ?: 'Not provided') . "
💬 Message: $message

Review ID: $testimonialId
⏰ Submitted: " . date('Y-m-d H:i:s') . "
🌐 IP Address: $clientIP

To approve this testimonial, you'll need to update the database status from 'pending' to 'approved'.

---
This email was automatically sent from " . SITE_NAME . "
        ";
        
        $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n" .
                   "Reply-To: " . ($email ?: FROM_EMAIL) . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        @mail(NOTIFICATION_EMAIL, $subject, $emailMessage, $headers);
    }
    
    // Success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you for your review! It will be published after approval.',
        'id' => $testimonialId
    ]);
    
    
} catch (PDOException $e) {
    error_log("Database error in savereview.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save review. Please try again.']);
} catch (Exception $e) {
    error_log("Error in savereview.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error occurred.']);
}
?>