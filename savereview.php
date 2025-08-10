<?php
$servername = "localhost"; // Change if needed
$username = "root";        // Your DB username
$password = "";            // Your DB password
$dbname = "mahadev_tent";    // Your DB name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}

$name = $_POST['name'];
$rating = $_POST['rating'];
$message = $_POST['message'];

$sql = "INSERT INTO testimonials (name, rating, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $name, $rating, $message);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Review saved!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save review"]);
}

$stmt->close();
$conn->close();
?>
