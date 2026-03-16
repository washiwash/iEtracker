<?php
declare(strict_types=1);
session_start();

$pdo = require __DIR__ . "/../../database/ietracker_database.php";

$error = "";
$registered = (isset($_GET["registered"]) && $_GET["registered"] === "1");
$hasIsActiveColumn = false;

try {
  $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
  $hasIsActiveColumn = $columnCheck !== false && $columnCheck->fetch() !== false;
} catch (PDOException $e) {
  $hasIsActiveColumn = false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $pass  = (string)($_POST["password"] ?? "");

  if ($email === "" || $pass === "") {
    $error = "Please enter your email and password.";
  } else {
    $selectColumns = "id, full_name, email, job_title, role, password_hash";
    if ($hasIsActiveColumn) {
      $selectColumns .= ", is_active";
    }

    $stmt = $pdo->prepare("SELECT " . $selectColumns . " FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user["password_hash"])) {
      $error = "Invalid email or password.";
    } elseif ($hasIsActiveColumn && (int)($user["is_active"] ?? 1) !== 1) {
      $error = "Your account is deactivated. Please contact your administrator.";
    } else {
      $_SESSION["user_id"] = (int)$user["id"];
      $_SESSION["full_name"] = (string)$user["full_name"];
      $_SESSION["email"] = (string)($user["email"] ?? "");
      $_SESSION["job_title"] = (string)($user["job_title"] ?? "None");
      $_SESSION["role"] = (string)($user["role"] ?? "user");
      header("Location: ../../index.php");
      exit;
    }
  }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo.svg" />
    <title>iEtracker - Log in Account</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../styles/login.css" />
  </head>
  <body>
    <div class="container-login">
      <div class="logo-circle">
        <img src="../../resources/images/logo.svg" alt="Logo" />
      </div>

      <div class="title">Welcome to <span>iEtracker</span></div>
      <div class="subtitle">Sign in to access your workspace</div>

      <div class="login-card">
        <?php if ($registered): ?>
          <div class="mb-2 text-success">Account created. Please sign in.</div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
          <div class="mb-2 text-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <label class="form-label">Email Address</label>
          <div class="input-group mb-3">
            <span class="input-group-text">
              <i class="bi bi-envelope"></i>
            </span>
            <input name="email" type="email" class="form-control" placeholder="you@example.com" required />
          </div>

          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-lock"></i>
            </span>
            <input name="password" type="password" class="form-control" placeholder="Enter your password" required />
            <span class="input-group-text">
              <i class="bi bi-eye"></i>
            </span>
          </div>

          <a href="#" class="forgot">Forgot password?</a>

          <button class="signin-btn" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
          </button>

          <div class="bottom-text">
            Don't have an account?
            <a href="register.php">Create Account</a>
          </div>
        </form>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>