<?php
require_once __DIR__ . '/../../middleware/auth_middleware.php';
requireRole(['admin']);

date_default_timezone_set("Asia/Manila");

$pdo = require __DIR__ . "/../../database/ietracker_database.php";
$currentUserId = (int) ($_SESSION["user_id"] ?? 0);

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :id LIMIT 1");
$stmt->execute(["id" => $currentUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fullName = trim((string) ($user["full_name"] ?? ($_SESSION["full_name"] ?? "")));
$fullName = $fullName !== "" ? $fullName : "User";

$nameParts = preg_split('/\s+/', $fullName) ?: [];
$firstInitial = (string) substr((string) ($nameParts[0] ?? ""), 0, 1);
$lastInitial = (string) substr((string) (count($nameParts) > 1 ? end($nameParts) : ""), 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);
$initials = $initials !== "" ? $initials : strtoupper(substr($fullName, 0, 1));
$initials = $initials !== "" ? $initials : "U";

$feedbackSuccess = "";
$feedbackError = "";
$hasIsActiveColumn = false;

if (!isset($_SESSION["csrf_token"])) {
  $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = (string) $_SESSION["csrf_token"];

try {
  $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
  $hasIsActiveColumn = $columnCheck !== false && $columnCheck->fetch() !== false;

  if (!$hasIsActiveColumn) {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
    $hasIsActiveColumn = true;
  }
} catch (PDOException $e) {
  $feedbackError = "Unable to initialize user status column. Please check DB permissions.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $hasIsActiveColumn) {
  $postedToken = (string) ($_POST["csrf_token"] ?? "");
  if (!hash_equals($csrfToken, $postedToken)) {
    $feedbackError = "Invalid request token. Refresh and try again.";
  } else {
    $action = (string) ($_POST["action"] ?? "");
    $targetUserId = (int) ($_POST["target_user_id"] ?? 0);

    if ($targetUserId <= 0) {
      $feedbackError = "Invalid user selected.";
    } elseif ($targetUserId === $currentUserId) {
      $feedbackError = "You cannot deactivate your own account.";
    } else {
      try {
        if ($action === "deactivate") {
          $deactivateStmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? LIMIT 1");
          $deactivateStmt->execute([$targetUserId]);
          $feedbackSuccess = $deactivateStmt->rowCount() > 0
            ? "User deactivated successfully."
            : "No changes made. User may already be deactivated.";
        } elseif ($action === "activate") {
          $activateStmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ? LIMIT 1");
          $activateStmt->execute([$targetUserId]);
          $feedbackSuccess = $activateStmt->rowCount() > 0
            ? "User activated successfully."
            : "No changes made. User may already be active.";
        } else {
          $feedbackError = "Unknown action.";
        }
      } catch (PDOException $e) {
        $feedbackError = "Failed to update user status.";
      }
    }
  }
}

$users = [];
$totalUsers = 0;
$activeUsers = 0;
$deactivatedUsers = 0;

try {
  if ($hasIsActiveColumn) {
    $usersStmt = $pdo->query(
      "SELECT id, full_name, email, role, is_active
       FROM users
       ORDER BY id ASC"
    );
  } else {
    $usersStmt = $pdo->query(
      "SELECT id, full_name, email, role, 1 AS is_active
       FROM users
       ORDER BY id ASC"
    );
  }

  $users = $usersStmt !== false ? $usersStmt->fetchAll(PDO::FETCH_ASSOC) : [];

  foreach ($users as $u) {
    $isActive = (int) ($u["is_active"] ?? 1) === 1;
    if ($isActive) {
      $activeUsers++;
    } else {
      $deactivatedUsers++;
    }
  }

  $totalUsers = count($users);
} catch (PDOException $e) {
  $feedbackError = $feedbackError !== "" ? $feedbackError : "Unable to load users list.";
  $users = [];
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>iEtracker - User Management</title>
    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo.svg" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../styles/style.css" />
    <link rel="stylesheet" href="../../styles/admin_user_management.css" />
  </head>
  <body>
    <?php
    $basePath = "../../";
    require __DIR__ . "/../partials/admin_navbar.php";
    ?>

    <aside class="sidebar d-flex flex-column p-3">
      <div class="d-flex align-items-center justify-content-between mb-3 mt-3 p-2">
        <div class="d-flex align-items-center gap-2">
          <span class="rounded-pill bg-info text-dark fw-semibold px-2 py-1">
            <?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?>
          </span>
          <span class="text-white small fw-medium"><?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>'s Workspace</span>
        </div>
      </div>

      <span class="border border-bottom-0 border-info" style="--bs-border-opacity: 0.1"></span>

      <div class="d-flex flex-column gap-1 mb-4">
        <a href="./admin_team_overview.php" class="nav-link d-flex align-items-center gap-3 mt-3">
          <i class="bi bi-house-door"></i> Team Overview
        </a>
        <a href="./admin_user_management.php" class="nav-link active d-flex align-items-center gap-3 mt-1 text-info">
          <i class="bi bi-check2-square"></i> User Management
        </a>
        <a href="./admin_profile_settings.php" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-gear"></i> Profile Settings
        </a>
      </div>
    </aside>

    <main class="app-main">
      <div class="user-mgmt-content">
        <header class="user-mgmt-header">
          <div>
            <h2>User Management <span class="admin">Admin</span></h2>
            <p>View all registered accounts, edit roles, and manage user status</p>
          </div>
        </header>

        <?php if ($feedbackSuccess !== ""): ?>
          <div class="alert alert-success py-2"><?= htmlspecialchars($feedbackSuccess, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <?php if ($feedbackError !== ""): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($feedbackError, ENT_QUOTES, "UTF-8") ?></div>
        <?php endif; ?>

        <section class="user-stat-grid" aria-label="User stats">
          <article class="user-stat-card neutral">
            <div class="stat-label">Total Users <i class="bi bi-people"></i></div>
            <h3><?= (int) $totalUsers ?></h3>
          </article>

          <article class="user-stat-card active">
            <div class="stat-label">Active <i class="bi bi-person-check"></i></div>
            <h3><?= (int) $activeUsers ?></h3>
          </article>

          <article class="user-stat-card deactivated">
            <div class="stat-label">Deactivated <i class="bi bi-person-x"></i></div>
            <h3><?= (int) $deactivatedUsers ?></h3>
          </article>
        </section>

        <section class="user-tools-row" aria-label="Filters">
          <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="userSearchInput" placeholder="Search by name, email, or role..." />
          </div>

          <div class="filter-tabs" role="tablist" aria-label="Status filters">
            <button type="button" class="filter-tab active" data-filter="all">All</button>
            <button type="button" class="filter-tab" data-filter="active">Active</button>
            <button type="button" class="filter-tab" data-filter="deactivated">Deactivated</button>
          </div>
        </section>

        <section class="table-shell" aria-label="Users table">
          <table class="user-table">
            <thead>
              <tr>
                <th>USER</th>
                <th>EMAIL</th>
                <th>INTERN ROLE</th>
                <th>TYPE</th>
                <th>STATUS</th>
                <th class="text-end">ACTIONS</th>
              </tr>
            </thead>
            <tbody id="userTableBody">
              <?php foreach ($users as $row): ?>
                <?php
                $userName = trim((string) ($row["full_name"] ?? "User"));
                $userEmail = trim((string) ($row["email"] ?? ""));
                $userRole = trim((string) ($row["role"] ?? ""));
                $userRole = $userRole !== "" ? $userRole : "Team Member";
                $isActive = (int) ($row["is_active"] ?? 1) === 1;
                $isCurrentUser = (int) ($row["id"] ?? 0) === $currentUserId;
                $typeLabel = strtolower($userRole) === "admin" ? "Admin" : "User";

                $parts = preg_split('/\s+/', $userName) ?: [];
                $rowInitials = strtoupper((string) substr((string) ($parts[0] ?? ""), 0, 1) . (string) substr((string) (count($parts) > 1 ? end($parts) : ""), 0, 1));
                $rowInitials = $rowInitials !== "" ? $rowInitials : "U";
                ?>
                <tr
                  data-filter-status="<?= $isActive ? "active" : "deactivated" ?>"
                  data-search-text="<?= htmlspecialchars(strtolower($userName . " " . $userEmail . " " . $userRole), ENT_QUOTES, "UTF-8") ?>">
                  <td>
                    <div class="user-cell">
                      <span class="avatar-pill"><?= htmlspecialchars($rowInitials, ENT_QUOTES, "UTF-8") ?></span>
                      <div>
                        <div class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES, "UTF-8") ?>
                          <?php if ($isCurrentUser): ?>
                            <span class="you-pill">You</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="email-cell"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($userEmail, ENT_QUOTES, "UTF-8") ?></td>
                  <td><?= htmlspecialchars($userRole, ENT_QUOTES, "UTF-8") ?></td>
                  <td>
                    <span class="type-pill <?= strtolower($typeLabel) === "admin" ? "admin" : "user" ?>">
                      <?= htmlspecialchars($typeLabel, ENT_QUOTES, "UTF-8") ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-pill <?= $isActive ? "active" : "deactivated" ?>">
                      <i class="bi bi-dot"></i><?= $isActive ? "Active" : "Deactivated" ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <?php if ($isCurrentUser): ?>
                      <span class="self-label">Current user</span>
                    <?php elseif ($isActive): ?>
                      <form method="POST" class="d-inline-block" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="action" value="deactivate">
                        <input type="hidden" name="target_user_id" value="<?= (int) ($row["id"] ?? 0) ?>">
                        <button type="submit" class="action-btn danger">
                          <i class="bi bi-person-x"></i>
                          Deactivate
                        </button>
                      </form>
                    <?php else: ?>
                      <form method="POST" class="d-inline-block" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="target_user_id" value="<?= (int) ($row["id"] ?? 0) ?>">
                        <button type="submit" class="action-btn restore">
                          <i class="bi bi-arrow-clockwise"></i>
                          Activate
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p id="userEmptyState" class="empty-state d-none">No users match your filters.</p>
        </section>
      </div>
    </main>

    <?php require __DIR__ . "/../partials/admin_help_modal.php"; ?>

    <script src="../../scripts/partial.js"></script>
    <script>
      (function () {
        const searchInput = document.getElementById("userSearchInput");
        const filterTabs = Array.from(document.querySelectorAll(".filter-tab"));
        const rows = Array.from(document.querySelectorAll("#userTableBody tr"));
        const emptyState = document.getElementById("userEmptyState");

        let currentFilter = "all";

        function applyFilters() {
          const query = (searchInput?.value || "").trim().toLowerCase();
          let visibleCount = 0;

          rows.forEach((row) => {
            const rowStatus = row.getAttribute("data-filter-status") || "all";
            const rowSearchText = row.getAttribute("data-search-text") || "";

            const matchesStatus = currentFilter === "all" || rowStatus === currentFilter;
            const matchesQuery = query === "" || rowSearchText.includes(query);
            const show = matchesStatus && matchesQuery;

            row.classList.toggle("d-none", !show);
            if (show) {
              visibleCount += 1;
            }
          });

          if (emptyState) {
            emptyState.classList.toggle("d-none", visibleCount > 0);
          }
        }

        if (searchInput) {
          searchInput.addEventListener("input", applyFilters);
        }

        filterTabs.forEach((tab) => {
          tab.addEventListener("click", () => {
            filterTabs.forEach((item) => item.classList.remove("active"));
            tab.classList.add("active");
            currentFilter = tab.getAttribute("data-filter") || "all";
            applyFilters();
          });
        });

        applyFilters();
      })();
    </script>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
      crossorigin="anonymous"></script>
  </body>
</html>
