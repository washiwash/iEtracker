<?php
declare(strict_types=1);

$pdo = require __DIR__ . "/../../database/ietracker_database.php";

$error = "";
$success = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $fullName = trim($_POST["full_name"] ?? "");
  $email    = trim($_POST["email"] ?? "");
  $jobTitle = trim($_POST["job_title"] ?? "");
  $pass     = (string)($_POST["password"] ?? "");
  $confirm  = (string)($_POST["confirm_password"] ?? "");

  if ($fullName === "" || $email === "" || $pass === "" || $confirm === "") {
    $error = "Please fill in all required fields.";
  } elseif (!preg_match("/^[A-Za-z]+(\s[A-Za-z]+)*$/", $fullName)) {
    $error = "Full name should contain only letters and single spaces between words.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $error = "Please enter a valid email address.";
} elseif (!in_array(substr(strrchr($email, "@"), 1), ["gmail.com", "yahoo.com", "outlook.com", "company.com"])) {
  $error = "Email domain not allowed. Please use a company email or approved domain.";
}
  elseif ($pass !== $confirm) {
    $error = "Passwords do not match.";
  } elseif (strlen($pass) < 8) {
    $error = "Password must be at least 8 characters.";
  }elseif (!preg_match("/[A-Z]/", $pass)) {
    $error = "Password must contain at least one uppercase letter.";}
    elseif (!preg_match("/[a-z]/", $pass)) {
    $error = "Password must contain at least one lowercase letter.";}
      elseif (!preg_match("/[0-9]/", $pass)) {
    $error = "Password must contain at least one number.";}
       elseif (!preg_match("/[\W_]/", $pass)) {
    $error = "Password must contain at least one special character.";}

   else {
    try {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, job_title, role, password_hash, days_absent) VALUES (?, ?, ?, ?, ?, 0)"
      );
      $stmt->execute([$fullName, $email, $jobTitle, "user", $hash]);

      $success = true;
    } catch (PDOException $e) {
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
          <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
          </div>
        <?php endif; ?>

        <!-- Success Modal -->
        <?php if ($success): ?>
        <div class="success-modal-overlay" id="successModal">
          <div class="success-modal">
            <div class="success-icon">
              <i class="bi bi-check-circle"></i>
            </div>
            <h2>Registration Successful!</h2>
            <p>Your account has been created successfully.</p>
            <p class="redirect-message">Redirecting to login in <span id="countdown">5</span> seconds...</p>
          </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
          <label>Full Name</label>
<input name="full_name" type="text" placeholder="e.g. John Doe" 
       pattern="[A-Za-z]+(\s[A-Za-z]+)*" 
       title="Full name should contain only letters and single spaces between words"
       value="<?= htmlspecialchars($_POST["full_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
      maxlength="70"
       required />
<div class="validation-error" id="fullNameError"></div>
          <label>Email Address</label>
          <input name="email"  type="email" placeholder="you@example.com"
          value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>" maxlength="254" required />
<div class="validation-error" id="emailError"></div>
          <label>Role/Position</label>
          <select name="job_title" id="job_title"  required>
            <option value="" disabled selected>Choose your role</option>
            <option value="Product Manager">Product Manager</option>
             <option value="Designer">Designer</option>
             <option value="Frontend Developer">Frontend Developer</option>
             <option value="Backend Developer">Backend Developer</option>
             <option value="FullStack Developer">Fullstack Developer</option>
              <option value="QA Engineer">QA Engineer</option>
              <option value="DevOps Engineer">DevOps Engineer</option>
            <option value="Content Writer">Content Writer</option>
              <option value="Marketing">Marketing</option>
               <option value="Other">Other</option>
            <option value="Designer">Designer</option>
         
          </select>
<div class="validation-error" id="jobTitleError"></div>

          <label>Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" placeholder="Create a password" maxlength="128"
 required />
            <span class = "password-btn text-light" ><i class="password-eye bi bi-eye-slash-fill"></i></span>
          
          </div>
          <div class="password-requirements">
            <div class="requirement" id="req-length">
              <i class="bi bi-x-circle req-icon"></i>
              <span>8+ characters</span>
            </div>
            <div class="requirement" id="req-uppercase">
              <i class="bi bi-x-circle req-icon"></i>
              <span>Uppercase</span>
            </div>
            <div class="requirement" id="req-lowercase">
              <i class="bi bi-x-circle req-icon"></i>
              <span>Lowercase</span>
            </div>
            <div class="requirement" id="req-number">
              <i class="bi bi-x-circle req-icon"></i>
              <span>Number</span>
            </div>
            <div class="requirement" id="req-special">
              <i class="bi bi-x-circle req-icon"></i>
              <span>Special char</span>
            </div>
          </div>
<div class="validation-error" id="passwordError"></div>

          <label>Confirm Password</label>
          <div class="password-field">
            <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" maxlength="128" required />
          <span class = "confirm_password-btn text-light" ><i class="confirm_password-eye bi bi-eye-slash-fill"></i></span>
    
          </div>
<div class="validation-error" id="confirmPasswordError"></div>

         <div class="form-check mb-3 mx-1">
  <input type="checkbox" class="form-check-input" id="terms" name="terms" required />
  <label class="form-check-label" for="terms">I agree to the <a href="../legal/terms.php" class = "text-decoration-none text-info">Terms of Service</a> and <a href="../legal/privacy.php" class="text-decoration-none text-info">Privacy Policy</a>.</label>
</div>
<div class="validation-error" id="termsError"></div>

<button type="submit" disabled><i class="bi bi-person-add text-white"></i> Create Account</button>
<hr class="border border-primary border-1 opacity-55 w-65 text-center mx-auto my-4" />

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
  <script>
    // Success modal countdown
    <?php if ($success): ?>
    document.addEventListener('DOMContentLoaded', () => {
      let secondsLeft = 5;
      const countdownElement = document.getElementById('countdown');
      
      const countdown = setInterval(() => {
        secondsLeft--;
        countdownElement.textContent = secondsLeft;
        
        if (secondsLeft <= 0) {
          clearInterval(countdown);
          window.location.href = 'login.php?registered=1';
        }
      }, 1000);
    });
    <?php endif; ?>
  </script>
  
  </body>
</html>