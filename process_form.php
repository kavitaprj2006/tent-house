   <?php
   header('Content-Type: application/json');
   
   // Database configuration (update with your credentials)
   $db_host = 'localhost';
   $db_user = 'username';
   $db_pass = 'password';
   $db_name = 'mahadev_tent';
   
   // Connect to database
   $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
   
   if ($conn->connect_error) {
       die(json_encode(['success' => false, 'message' => 'Database connection failed']));
   }
   
   // Get form data
   $name = $_POST['name'] ?? '';
   $phone = $_POST['phone'] ?? '';
   $email = $_POST['email'] ?? '';
   $eventType = $_POST['eventType'] ?? '';
   $date = $_POST['date'] ?? '';
   $message = $_POST['message'] ?? '';
   
   // Validate inputs
   if (empty($name) || empty($phone) || empty($email) || empty($message)) {
       echo json_encode(['success' => false, 'message' => 'All fields are required']);
       exit;
   }
   
   // Insert into database
   $stmt = $conn->prepare("INSERT INTO inquiries (name, phone, email, event_type, event_date, message) VALUES (?, ?, ?, ?, ?, ?)");
   $stmt->bind_param("ssssss", $name, $phone, $email, $eventType, $date, $message);
   
   if ($stmt->execute()) {
       echo json_encode(['success' => true, 'message' => 'Thank you! Your inquiry has been received.']);
       
       // Optional: Send email notification
       $to = "kavitaprj2006@gmail.com";
       $subject = "New Inquiry from Mahadev Tent House Website";
       $emailBody = "You have received a new inquiry:\n\nName: $name\nPhone: $phone\nEmail: $email\nEvent Type: $eventType\nDate: $date\nMessage:\n$message";
       mail($to, $subject, $emailBody);
   } else {
       echo json_encode(['success' => false, 'message' => 'Error saving inquiry. Please try again.']);
   }
   
   $stmt->close();
   $conn->close();
   ?>
   
