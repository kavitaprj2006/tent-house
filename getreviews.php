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