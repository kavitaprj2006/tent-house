<?php
/**
 * Database Configuration for Mahadev Tent House
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mahadev_tent');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Database Connection Class
 */
class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

/**
 * Testimonials Handler Class
 */
class TestimonialsHandler {
    private $db;
    
    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->createTableIfNotExists();
    }
    
    /**
     * Create testimonials table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS testimonials (
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
        
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating testimonials table: " . $e->getMessage());
        }
    }
    
    /**
     * Get approved testimonials
     */
    public function getTestimonials($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT id, name, rating, message, created_at 
                    FROM testimonials 
                    WHERE status = 'approved' 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching testimonials: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Save new testimonial
     */
    public function saveTestimonial($name, $rating, $message, $email = null) {
        // Validate input
        $validation = $this->validateTestimonial($name, $rating, $message);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check for duplicate submissions (same IP within 1 hour)
        if ($this->isDuplicateSubmission()) {
            return [
                'success' => false, 
                'errors' => ['You have already submitted a review recently. Please wait before submitting another one.']
            ];
        }
        
        try {
            $sql = "INSERT INTO testimonials (name, email, rating, message, ip_address) 
                    VALUES (:name, :email, :rating, :message, :ip_address)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => trim($name),
                ':email' => $email ? trim($email) : null,
                ':rating' => (int)$rating,
                ':message' => trim($message),
                ':ip_address' => $this->getClientIP()
            ]);
            
            return [
                'success' => true, 
                'message' => 'Thank you for your review! It will be published after approval.',
                'id' => $this->db->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("Error saving testimonial: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to save testimonial. Please try again.']];
        }
    }
    
    /**
     * Validate testimonial data
     */
    private function validateTestimonial($name, $rating, $message) {
        $errors = [];
        
        // Name validation
        if (empty(trim($name))) {
            $errors[] = 'Name is required';
        } elseif (strlen(trim($name)) < 2) {
            $errors[] = 'Name must be at least 2 characters long';
        } elseif (strlen(trim($name)) > 100) {
            $errors[] = 'Name must be less than 100 characters';
        }
        
        // Rating validation
        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            $errors[] = 'Rating must be between 1 and 5';
        }
        
        // Message validation
        if (empty(trim($message))) {
            $errors[] = 'Message is required';
        } elseif (strlen(trim($message)) < 10) {
            $errors[] = 'Message must be at least 10 characters long';
        } elseif (strlen(trim($message)) > 1000) {
            $errors[] = 'Message must be less than 1000 characters';
        }
        
        // Check for spam patterns
        if ($this->containsSpam($name . ' ' . $message)) {
            $errors[] = 'Your message contains inappropriate content';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Check for duplicate submissions
     */
    private function isDuplicateSubmission() {
        try {
            $sql = "SELECT COUNT(*) FROM testimonials 
                    WHERE ip_address = :ip_address 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':ip_address' => $this->getClientIP()]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking duplicate submission: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Basic spam detection
     */
    private function containsSpam($text) {
        $spamWords = ['viagra', 'casino', 'poker', 'loan', 'debt', 'free money', 'click here', 'buy now'];
        $text = strtolower($text);
        
        foreach ($spamWords as $word) {
            if (strpos($text, $word) !== false) {
                return true;
            }
        }
        
        // Check for excessive links
        if (preg_match_all('/http[s]?:\/\//', $text) > 2) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get testimonials statistics
     */
    public function getStatistics() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_count,
                        AVG(rating) as average_rating,
                        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                        COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_count
                    FROM testimonials";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching statistics: " . $e->getMessage());
            return [
                'total_count' => 0,
                'average_rating' => 0,
                'approved_count' => 0,
                'recent_count' => 0
            ];
        }
    }
}

/**
 * API Endpoints
 */

// Set content type
header('Content-Type: application/json');

// Enable CORS (adjust origins as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize handler
$testimonials = new TestimonialsHandler();

// Route requests
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($path) {
        case '/api/testimonials':
            if ($method === 'GET') {
                // Get testimonials
                $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
                $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
                
                $data = $testimonials->getTestimonials($limit, $offset);
                echo json_encode(['success' => true, 'data' => $data]);
                
            } elseif ($method === 'POST') {
                // Save new testimonial
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    // Try form data
                    $input = $_POST;
                }
                
                $name = $input['name'] ?? '';
                $rating = $input['rating'] ?? '';
                $message = $input['message'] ?? '';
                $email = $input['email'] ?? null;
                
                $result = $testimonials->saveTestimonial($name, $rating, $message, $email);
                
                if ($result['success']) {
                    http_response_code(201);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode($result);
                }
                
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        case '/api/testimonials/stats':
            if ($method === 'GET') {
                $stats = $testimonials->getStatistics();
                echo json_encode(['success' => true, 'data' => $stats]);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Additional utility functions for admin panel (optional)
 */
class TestimonialsAdmin extends TestimonialsHandler {
    
    /**
     * Get all testimonials (including pending/rejected)
     */
    public function getAllTestimonials($limit = 20, $offset = 0, $status = null) {
        try {
            $sql = "SELECT id, name, email, rating, message, status, ip_address, created_at 
                    FROM testimonials";
            
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching all testimonials: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update testimonial status
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['pending', 'approved', 'rejected'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        
        try {
            $sql = "UPDATE testimonials SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => $status, ':id' => (int)$id]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Status updated successfully'];
            } else {
                return ['success' => false, 'error' => 'Testimonial not found'];
            }
            
        } catch (PDOException $e) {
            error_log("Error updating testimonial status: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update status'];
        }
    }
    
    /**
     * Delete testimonial
     */
    public function deleteTestimonial($id) {
        try {
            $sql = "DELETE FROM testimonials WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Testimonial deleted successfully'];
            } else {
                return ['success' => false, 'error' => 'Testimonial not found'];
            }
            
        } catch (PDOException $e) {
            error_log("Error deleting testimonial: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete testimonial'];
        }
    }
    
    /**
     * Bulk approve testimonials
     */
    public function bulkApprove($ids) {
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'error' => 'No testimonials selected'];
        }
        
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "UPDATE testimonials SET status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);
            
            return ['success' => true, 'message' => "Approved {$stmt->rowCount()} testimonials"];
            
        } catch (PDOException $e) {
            error_log("Error bulk approving testimonials: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to approve testimonials'];
        }
    }
}

?>

<?php
/**
 * Separate file: getreviews.php
 * Simple endpoint for fetching approved testimonials
 */

// Uncomment and save as separate file: getreviews.php
/*
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'testimonials.php';

try {
    $testimonials = new TestimonialsHandler();
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    
    $data = $testimonials->getTestimonials($limit, $offset);
    echo json_encode($data);
    
} catch (Exception $e) {
    error_log("Error in getreviews.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load testimonials']);
}
?>
*/

/**
 * Separate file: savereview.php  
 * Simple endpoint for saving new testimonials
 */

// Uncomment and save as separate file: savereview.php
/*
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'testimonials.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $testimonials = new TestimonialsHandler();
    
    $name = $_POST['name'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $message = $_POST['message'] ?? '';
    $email = $_POST['email'] ?? null;
    
    $result = $testimonials->saveTestimonial($name, $rating, $message, $email);
    
    if ($result['success']) {
        echo json_encode(['status' => 'success', 'message' => $result['message']]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'errors' => $result['errors']]);
    }
    
} catch (Exception $e) {
    error_log("Error in savereview.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save review']);
}
?>
*/

/**
 * Database Setup Script
 * Run this once to set up your database
 */

// Uncomment and save as separate file: setup_database.php
/*
<?php
// Database setup script - run once
require_once 'testimonials.php';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully!\n";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Initialize testimonials handler (this will create the table)
    $testimonials = new TestimonialsHandler();
    echo "Testimonials table created successfully!\n";
    
    // Insert sample testimonials
    $sampleTestimonials = [
        ['Rajesh Kumar', 5, 'Excellent service for our wedding! The tent decoration was beautiful and the team was very professional. Highly recommended!'],
        ['Priya Sharma', 5, 'Amazing work for our daughter\'s birthday party. The lighting and decorations exceeded our expectations. Thank you Mahadev Tent House!'],
        ['Vikram Singh', 4, 'Good quality tents and timely setup for our corporate event. Professional service at reasonable rates.'],
        ['Sunita Devi', 5, 'Perfect arrangements for our religious function. The team was respectful and handled everything with care.'],
        ['Amit Joshi', 4, 'Great service for our anniversary celebration. Beautiful decorations and professional staff.']
    ];
    
    foreach ($sampleTestimonials as $testimonial) {
        $result = $testimonials->saveTestimonial($testimonial[0], $testimonial[1], $testimonial[2]);
        if ($result['success']) {
            // Auto-approve sample testimonials
            $admin = new TestimonialsAdmin();
            $admin->updateStatus($result['id'], 'approved');
            echo "Added testimonial from {$testimonial[0]}\n";
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    echo "You can now use the testimonials system.\n";
    
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
?>
*/