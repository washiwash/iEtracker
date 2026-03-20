<?php
declare(strict_types=1);

$pdo = require __DIR__ . "/../../database/ietracker_database.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $fullName = trim($_POST["full_name"] ?? "");
  $email    = trim($_POST["email"] ?? "");
  $jobTitle = trim($_POST["job_title"] ?? "");
  $pass     = (string)($_POST["password"] ?? "");
  $confirm  = (string)($_POST["confirm_password"] ?? "");

  if ($fullName === "" || $email === "" || $pass === "" || $confirm === "") {
    $error = "Please fill in all required fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
  } elseif ($pass !== $confirm) {
    $error = "Passwords do not match.";
  } elseif (strlen($pass) < 8) {
    $error = "Password must be at least 8 characters.";
  } else {
    try {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, job_title, role, password_hash, days_absent) VALUES (?, ?, ?, ?, ?, 0)"
      );
      $stmt->execute([$fullName, $email, $jobTitle, "user", $hash]);

      header("Location: login.php?registered=1");
      exit;
    } catch (PDOException $e) {
      // Duplicate email usually ends up as SQLSTATE 23000 (integrity constraint)
      if (($e->getCode() ?? "") === "23000") {
        $error = "That email is already registered.";
      } else {
        $error = "Registration failed. Please try again.";
      }
    }
  }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo.svg" />
    <title>iEtracker - Create Account</title>
     <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../styles/register.css" />
  </head>

  <body>
    <div class="container d-flex flex-column justify-content-center align-items-center ">
      <div>
        <img src="../../resources/images/logo.svg" alt="iEtracker logo" />
      </div>

      <div class="title">Create your <span>iEtracker</span> account</div>
      <div class="subtitle">Fill in your details to get started</div>

      <div class="card px-4 py-3">
        <?php if ($error !== ""): ?>
          <div style="color: #b00020; margin-bottom: 12px;">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="" >
          <label>Full Name</label>
          <input name="full_name" type="text" placeholder="e.g. John Doe" required />

          <label>Email Address</label>
          <input name="email" type="email" placeholder="you@example.com" required />

          <label>Job Title</label>
          <input name="job_title" type="text" placeholder="add job title" />

          <label>Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" placeholder="Create a password" required />
            <span class = "password-btn text-light" ><i class="password-eye bi bi-eye-slash-fill"></i></span>
  
          </div>

          <label>Confirm Password</label>
          <div class="password-field">
            <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" required />
          <span class = "confirm_password-btn text-light" ><i class="confirm_password-eye bi bi-eye-slash-fill"></i></span>
    
          </div>

          <button type="submit">👤 Create Account</button>

          <div class="footer">
            Already have an account? <a href="login.php">Sign In</a>
          </div>
        </form>
      </div>
    </div>
     <script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
  crossorigin="anonymous"></script>
  <script src="../../scripts/authentication.js"></script>
  
  </body>
</html>