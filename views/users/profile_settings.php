<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: views/authenticator/login.php");
  exit;
}

$pdo = require __DIR__ . "/../../database/ietracker_database.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $newFullName = trim((string)($_POST["full_name"] ?? ""));
  $newEmail = trim((string)($_POST["email"] ?? ""));

  if ($newFullName === "" || $newEmail === "") {
    $error = "Please fill in all fields.";
  } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
  } else {
    try {
      $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
      $stmt->execute([$newFullName, $newEmail, (int)$_SESSION["user_id"]]);

      $_SESSION["full_name"] = $newFullName;
      $_SESSION["email"] = $newEmail;

      $success = "Profile updated successfully.";
    } catch (PDOException $e) {
      if (($e->getCode() ?? "") === "23000") {
        $error = "That email is already in use.";
      } else {
        $error = "Failed to update profile. Please try again.";
      }
    }
  }
}

$fullName = (string)($_SESSION["full_name"] ?? "User");
$email = (string)($_SESSION["email"] ?? "None");
$role = (string)($_SESSION["role"] ?? "None");

$parts = preg_split('/\s+/', trim($fullName));
$initials = "";
foreach (array_slice($parts ?: [], 0, 2) as $p) {
  $initials .= strtoupper(substr($p, 0, 1));
}
if ($initials === "") $initials = "U";
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>iEtracker - Profile Settings</title>
    <link
      rel="icon"
      type="image/svg+xml"
      href="../../resources/images/logo.svg" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../styles/style.css" />
  </head>
  <body>
   <?php
$basePath = "../../";
require __DIR__ . "/../partials/navbar.php";
?>

    <aside class="sidebar d-flex flex-column p-3">
      <div class="d-flex align-items-center justify-content-between mb-3 mt-3 p-2">
        <div class="d-flex align-items-center gap-2">
         <span class="rounded-pill bg-info text-dark fw-semibold px-2 py-1">
  <?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?>
</span>
         <span class="text-white small fw-medium"><?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>'s Workspace</span>
        </div>

        <!-- <i class="bi bi-caret-down-fill text-secondary"></i> -->
      </div>


      <span
        class="border border-bottom-0 border-info"
        style="--bs-border-opacity: 0.1"></span>
      <div class="d-flex flex-column gap-1 mb-4">
        <a
          href="../../index.php"
          class="nav-link d-flex align-items-center gap-3 mt-3">
          <i class="bi bi-house-door"></i> Dashboard
        </a>
        <a
          href="./task_tracker.php"
          class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-check2-square"></i> Task Tracker
        </a>
        <a
          href="./attendance.php"
          class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-person"></i> My Attendance
        </a>
        <a
          href="./profile_settings.php"
          class="nav-link active d-flex align-items-center gap-3 text-info">
          <i class="bi bi-gear"></i> Profile Settings
        </a>
      </div>

      <!-- <div class="d-flex flex-column gap-1">
        <div
          class="d-flex align-items-center justify-content-between px-2 mb-2">
          <small
            class="text-uppercase text-secondary fw-bold"
            style="font-size: 0.7rem; letter-spacing: 1px"
            >Projects</small
          >
          <button class="btn btn-sm p-0 text-white">
            <i class="bi bi-plus-lg"></i>
          </button>
        </div>
        <a
          href="./project_ietracker.html"
          class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-circle-fill fs-6 text-info"></i> Project iETracker
        </a>
        <a
          href="./project_marketing_site.html"
          class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-circle-fill fs-6 text-warning"></i> Marketing Site
        </a>
      </div> -->
    </aside>

    <main class="app-main profile-page">
      <section class="profile-shell">
        <h1 class="profile-title">Profile Settings</h1>
        <p class="profile-subtitle">
          Manage your account details and security settings
        </p>

        <article class="profile-hero-card">
          <div class="profile-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?></div>
          <div>
            <h2 class="profile-name"><?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?></h2>
            <p class="profile-role">Role: <?= htmlspecialchars($role, ENT_QUOTES, "UTF-8") ?></p>
          </div>
        </article>

        <nav class="profile-tabs" aria-label="Profile tabs">
          <a href="./profile_settings.php" class="profile-tab active text-decoration-none">
            <i class="bi bi-person"></i>
            <span>Account Details</span>
          </a>
          <a href="./profile_security.php" class="profile-tab text-decoration-none">
            <i class="bi bi-lock"></i>
            <span>Security</span>
          </a>
        </nav>

        <section class="profile-form-card" aria-label="Personal information">
          <h3 class="profile-form-title">Personal Information</h3>

          <?php if ($success !== ""): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?></div>
          <?php endif; ?>
          <?php if ($error !== ""): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
          <?php endif; ?>

          <form method="POST" action="">

          <div class="profile-form-grid">
            <label class="profile-field">
              <span class="profile-label">Full Name</span>
              <span class="profile-input-wrap">
                <i class="bi bi-person"></i>
                <input
                  type="text"
                  name="full_name"
                  value="<?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>"
                  required />
              </span>
            </label>

            <label class="profile-field">
              <span class="profile-label">Email Address</span>
              <span class="profile-input-wrap">
                <i class="bi bi-envelope"></i>
                <input
                  type="email"
                  name="email"
                  value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                  required />
              </span>
            </label>

            <!-- <label class="profile-field">
              <span class="profile-label">Username</span>
              <span class="profile-input-wrap">
                <i class="bi bi-person"></i>
                <input type="text" value="username" placeholder="username" />
              </span>
            </label> -->
          </div>

          <div class="profile-actions">
            <button type="submit" class="profile-save-btn">
              <i class="bi bi-floppy"></i>
              <span>Save Changes</span>
            </button>
          </div>
          </form>
        </section>
      </section>
    </main>

    <?php require __DIR__ . "/../partials/help_modal.php"; ?>

    <script src="../../scripts/partial.js"></script>
    <script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
  crossorigin="anonymous"></script>
  </body>
</html>
