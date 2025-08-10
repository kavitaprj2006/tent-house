<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mahadev_tent";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode([]));
}

$sql = "SELECT * FROM testimonials ORDER BY created_at DESC";
$result = $conn->query($sql);

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

echo json_encode($reviews);
$conn->close();
?>
