<?php
declare(strict_types=1);
session_start();

date_default_timezone_set("Asia/Manila");

if (!isset($_SESSION["user_id"])) {
  header("Location: ../authenticator/login.php");
  exit;
}

$pdo = require __DIR__ . "/../../database/ietracker_database.php";

$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :id LIMIT 1");
$stmt->execute(["id" => $_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fullName = trim((string) ($user["full_name"] ?? ($_SESSION["full_name"] ?? "")));
$fullName = $fullName !== "" ? $fullName : "User";

$nameParts = preg_split('/\s+/', $fullName) ?: [];
$firstInitial = (string) substr((string) ($nameParts[0] ?? ""), 0, 1);
$lastInitial = (string) substr((string) (count($nameParts) > 1 ? end($nameParts) : ""), 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);
$initials = $initials !== "" ? $initials : strtoupper(substr($fullName, 0, 1));
$initials = $initials !== "" ? $initials : "U";

$teamMembers = [];
$clockedInCount = 0;
$notClockedInCount = 0;
$pendingTasksCount = 0;
$inProgressTasksCount = 0;
$completedTasksCount = 0;

function formatClockTime(?string $value): string
{
  if ($value === null || trim($value) === "") {
    return "--:--";
  }

  try {
    return (new DateTimeImmutable($value, new DateTimeZone("Asia/Manila")))->format("h:i A");
  } catch (Exception $e) {
    return "--:--";
  }
}

try {
  $teamStmt = $pdo->query(
    "SELECT
      u.id,
      u.full_name,
      COALESCE(NULLIF(u.role, ''), 'Team Member') AS role,
      attendance_today.first_time_in,
      attendance_today.last_time_out,
      attendance_today.has_open_shift,
      COALESCE(task_totals.pending_count, 0) AS pending_count,
      COALESCE(task_totals.progress_count, 0) AS progress_count,
      COALESCE(task_totals.completed_count, 0) AS completed_count
    FROM users u
    LEFT JOIN (
      SELECT
        a.user_id,
        MIN(a.time_in) AS first_time_in,
        MAX(a.time_out) AS last_time_out,
        MAX(CASE WHEN a.time_out IS NULL THEN 1 ELSE 0 END) AS has_open_shift
      FROM attendance a
      WHERE DATE(a.time_in) = CURDATE()
      GROUP BY a.user_id
    ) AS attendance_today ON attendance_today.user_id = u.id
    LEFT JOIN (
      SELECT
        t.user_id,
        SUM(CASE WHEN t.status IN ('pending', 'due') THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS progress_count,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
      FROM tasks t
      GROUP BY t.user_id
    ) AS task_totals ON task_totals.user_id = u.id
    WHERE LOWER(COALESCE(u.role, '')) <> 'admin'
    ORDER BY u.full_name ASC"
  );

  $teamRows = $teamStmt->fetchAll();

  foreach ($teamRows as $row) {
    $hasOpenShift = (int) ($row["has_open_shift"] ?? 0) === 1;
    $hasTimeInToday = isset($row["first_time_in"]) && (string) $row["first_time_in"] !== "";
    $hasTimeOutToday = isset($row["last_time_out"]) && (string) $row["last_time_out"] !== "";

    $status = "Not Clocked In";
    $statusClass = "status-offline";
    if ($hasOpenShift) {
      $status = "Clocked In";
      $statusClass = "status-in";
      $clockedInCount++;
    } else {
      $notClockedInCount++;
      if ($hasTimeInToday && $hasTimeOutToday) {
        $status = "Clocked Out";
        $statusClass = "status-out";
      }
    }

    $pendingCount = (int) ($row["pending_count"] ?? 0);
    $progressCount = (int) ($row["progress_count"] ?? 0);
    $completedCount = (int) ($row["completed_count"] ?? 0);

    $pendingTasksCount += $pendingCount;
    $inProgressTasksCount += $progressCount;
    $completedTasksCount += $completedCount;

    $teamMembers[] = [
      "name" => (string) ($row["full_name"] ?? "User"),
      "role" => (string) ($row["role"] ?? "Team Member"),
      "status" => $status,
      "status_class" => $statusClass,
      "in" => formatClockTime(isset($row["first_time_in"]) ? (string) $row["first_time_in"] : null),
      "out" => formatClockTime(isset($row["last_time_out"]) ? (string) $row["last_time_out"] : null),
      "pending" => $pendingCount,
      "progress" => $progressCount,
      "done" => $completedCount,
    ];
  }
} catch (PDOException $e) {
  $teamMembers = [];
  $clockedInCount = 0;
  $notClockedInCount = 0;
  $pendingTasksCount = 0;
  $inProgressTasksCount = 0;
  $completedTasksCount = 0;
}

$teamCount = count($teamMembers);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>iEtracker - Dashboard</title>
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
     <link rel="stylesheet" href="../../styles/admin.css" />
  </head>
  <body>
    <?php
    $basePath = "../../";
    require __DIR__ . "/../partials/admin_navbar.php";
    ?>

    <aside class="sidebar d-flex flex-column p-3">
      <!-- Workspace Switcher Area -->
      <div class="d-flex align-items-center justify-content-between mb-3 mt-3 p-2">
        <div class="d-flex align-items-center gap-2">
         <span class="rounded-pill bg-info text-dark fw-semibold px-2 py-1">
  <?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?>
</span>
         <span class="text-white small fw-medium"><?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>'s Workspace</span>
        </div>

      </div>

      <span
        class="border border-bottom-0 border-info"
        style="--bs-border-opacity: 0.1"></span>
      <!-- Primary Navigation -->
      <div class="d-flex flex-column gap-1 mb-4">
        <a
          href="#"
          class="nav-link active d-flex align-items-center gap-3 mt-3 text-info">
          <i class="bi bi-house-door"></i> Team Overview
        </a>
        <a
          href="./admin_user_management.php"
          class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-check2-square"></i> User Management
        </a>
        <!-- <a href="../users/attendance.php" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-person"></i> My Attendance
        </a> -->
        <a href="./admin_profile_settings.php" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-gear"></i> Profile Settings
        </a>
      </div>

      
    </aside>

 <main class="app-main">

 <div class="content">

<div class="header">

<div>
<h2>Team Overview <span class="admin">Admin</span></h2>
<p style="color:#9AA6B2;font-size:14px;">
Real-time summary of your team's attendance and task status
</p>
</div>

<div style="color:#9AA6B2;font-size:14px;text-align:right;">
<?= htmlspecialchars(date("l, F j, Y"), ENT_QUOTES, "UTF-8") ?><br>
<?= htmlspecialchars((string) $teamCount, ENT_QUOTES, "UTF-8") ?> team members
</div>

</div>


<div class="cards">

<div class="card green">
<div class="card-title">Clocked In <i class="bi bi-person-check"></i></div>
<h3><?= htmlspecialchars((string) $clockedInCount, ENT_QUOTES, "UTF-8") ?></h3>
<span>of <?= htmlspecialchars((string) $teamCount, ENT_QUOTES, "UTF-8") ?> members</span>
</div>

<div class="card red">
<div class="card-title">Absent / Not In <i class="bi bi-person-x"></i></div>
<h3><?= htmlspecialchars((string) $notClockedInCount, ENT_QUOTES, "UTF-8") ?></h3>
<span>0 absent, <?= htmlspecialchars((string) $notClockedInCount, ENT_QUOTES, "UTF-8") ?> not clocked</span>
</div>

<div class="card yellow">
<div class="card-title">Pending Tasks <i class="bi bi-exclamation-circle"></i></div>
<h3><?= htmlspecialchars((string) $pendingTasksCount, ENT_QUOTES, "UTF-8") ?></h3>
<span>across team</span>
</div>

<div class="card blue">
<div class="card-title">In Progress <i class="bi bi-activity"></i></div>
<h3><?= htmlspecialchars((string) $inProgressTasksCount, ENT_QUOTES, "UTF-8") ?></h3>
<span>active tasks</span>
</div>

</div>


<div class="section">

<div class="section-title">Team Task Summary</div>

<div class="summary">

<div class="summary-box pending">
<h3><?= htmlspecialchars((string) $pendingTasksCount, ENT_QUOTES, "UTF-8") ?></h3>
Pending
</div>

<div class="summary-box in-progress">
<h3><?= htmlspecialchars((string) $inProgressTasksCount, ENT_QUOTES, "UTF-8") ?></h3>
In Progress
</div>

<div class="summary-box completed">
<h3><?= htmlspecialchars((string) $completedTasksCount, ENT_QUOTES, "UTF-8") ?></h3>
Completed
</div>

</div>

</div>

<div class="section section-team">

<div class="section-title d-flex align-items-center justify-content-between">
<span><i class="bi bi-people me-2"></i>Team Members</span>
<span class="section-meta"><?= htmlspecialchars((string) $teamCount, ENT_QUOTES, "UTF-8") ?> members</span>
</div>

<div class="members-list">

<?php foreach ($teamMembers as $member): ?>
<?php
$parts = preg_split('/\s+/', (string) $member["name"]) ?: [];
$memberInitials = strtoupper((string) substr((string) ($parts[0] ?? ""), 0, 1) . (string) substr((string) (count($parts) > 1 ? end($parts) : ""), 0, 1));
$memberInitials = $memberInitials !== "" ? $memberInitials : "U";
?>

<div class="member-row">

<div class="member-main">
<div class="user-avatar"><?= htmlspecialchars($memberInitials, ENT_QUOTES, "UTF-8") ?></div>
<div>
<div class="member-name"><?= htmlspecialchars((string) $member["name"], ENT_QUOTES, "UTF-8") ?></div>
<div class="member-role"><?= htmlspecialchars((string) $member["role"], ENT_QUOTES, "UTF-8") ?></div>
</div>
</div>

<div class="member-status-wrap">
<span class="status <?= htmlspecialchars((string) $member["status_class"], ENT_QUOTES, "UTF-8") ?>"><i class="bi bi-dot"></i><?= htmlspecialchars((string) $member["status"], ENT_QUOTES, "UTF-8") ?></span>
</div>

<div class="member-time">
<span>IN<br><strong><?= htmlspecialchars((string) $member["in"], ENT_QUOTES, "UTF-8") ?></strong></span>
<span>OUT<br><strong><?= htmlspecialchars((string) $member["out"], ENT_QUOTES, "UTF-8") ?></strong></span>
</div>

<div class="member-stats">
<span class="stat pending"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars((string) $member["pending"], ENT_QUOTES, "UTF-8") ?></span>
<span class="stat in-progress"><i class="bi bi-clock-history"></i><?= htmlspecialchars((string) $member["progress"], ENT_QUOTES, "UTF-8") ?></span>
<span class="stat done"><i class="bi bi-check-circle"></i><?= htmlspecialchars((string) $member["done"], ENT_QUOTES, "UTF-8") ?></span>
</div>

</div>
<?php endforeach; ?>

</div>

</div>

</div>
    </main>

    <?php require __DIR__ . "/../partials/admin_help_modal.php"; ?>

      <script src="../../scripts/partial.js"></script>
      <script src="../../scripts/script.js"></script>
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
  crossorigin="anonymous"></script>
  </body>
</html>
