<?php
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['valid' => false, 'error' => 'Not authenticated']);
  exit;
}

// Get the password from request
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (empty($password)) {
  echo json_encode(['valid' => false]);
  exit;
}

try {
  $pdo = require __DIR__ . "/../database/ietracker_database.php";
  
  // Get the user's current password hash
  $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
  $stmt->execute([(int)$_SESSION["user_id"]]);
  $row = $stmt->fetch();
  
  if (!$row) {
    echo json_encode(['valid' => false]);
    exit;
  }
  
  // Verify the password
  $isValid = password_verify($password, $row['password_hash']);
  
  echo json_encode(['valid' => $isValid]);
} catch (Exception $e) {
  echo json_encode(['valid' => false, 'error' => 'Database error']);
}
