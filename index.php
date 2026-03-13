<?php
declare(strict_types=1);
session_start();

date_default_timezone_set("Asia/Manila");

if (!isset($_SESSION["user_id"])) {
  header("Location: views/authenticator/login.php");
  exit;
}

$pdo = require __DIR__ . "/database/ietracker_database.php";

$taskError = "";
$taskSuccess = "";
$attendanceError = "";
$attendanceSuccess = "";
$userId = (int)($_SESSION["user_id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["attendance_action"])) {
  $attendanceAction = trim((string)($_POST["attendance_action"] ?? ""));

  if ($userId <= 0) {
    $attendanceError = "You must be signed in to record attendance.";
  } else {
    try {
      if ($attendanceAction === "time_in") {
        $existingTodayStmt = $pdo->prepare(
          "SELECT id
          FROM attendance
          WHERE user_id = ?
            AND DATE(time_in) = CURDATE()
          ORDER BY time_in DESC
          LIMIT 1"
        );
        $existingTodayStmt->execute([$userId]);
        $existingToday = $existingTodayStmt->fetch();

        if ($existingToday !== false) {
          $attendanceError = "You have already timed in today.";
        } else {
          $timeInStmt = $pdo->prepare(
            "INSERT INTO attendance (user_id, time_in) VALUES (?, NOW())"
          );
          $timeInStmt->execute([$userId]);
          $attendanceSuccess = "Time in recorded successfully.";
        }
      } elseif ($attendanceAction === "time_out") {
        $openAttendanceStmt = $pdo->prepare(
          "SELECT id
          FROM attendance
          WHERE user_id = ?
            AND DATE(time_in) = CURDATE()
            AND time_in IS NOT NULL
            AND time_out IS NULL
          ORDER BY time_in DESC
          LIMIT 1"
        );
        $openAttendanceStmt->execute([$userId]);
        $openAttendance = $openAttendanceStmt->fetch();

        if ($openAttendance === false) {
          $attendanceError = "Please time in first before timing out.";
        } else {
          $timeOutStmt = $pdo->prepare(
            "UPDATE attendance
            SET time_out = NOW()
            WHERE id = ? AND user_id = ?
            LIMIT 1"
          );
          $timeOutStmt->execute([(int)$openAttendance["id"], $userId]);
          $attendanceSuccess = "Time out recorded successfully.";
        }
      } else {
        $attendanceError = "Invalid attendance action.";
      }
    } catch (PDOException $e) {
      $attendanceError = "Unable to save attendance right now.";
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_task"])) {
  $taskName = trim((string)($_POST["task_name"] ?? ""));
  $taskDescription = trim((string)($_POST["task_description"] ?? ""));
  $dueDate = trim((string)($_POST["due_date"] ?? ""));
  $dueTime = trim((string)($_POST["due_time"] ?? ""));
  if ($userId <= 0 || $taskName === "" || $dueDate === "" || $dueTime === "") {
    $taskError = "Please complete all required fields.";
  } else {
    $dueAt = $dueDate . " " . $dueTime . ":00";
    $dateTime = DateTime::createFromFormat("Y-m-d H:i:s", $dueAt);

    if ($dateTime === false || $dateTime->format("Y-m-d H:i:s") !== $dueAt) {
      $taskError = "Please provide a valid due date and time.";
    } else {
      try {
        $stmt = $pdo->prepare(
          "INSERT INTO tasks (user_id, task_name, task_description, due_at, status)
           VALUES (?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$userId, $taskName, $taskDescription, $dueAt]);
        $taskSuccess = "Task created successfully.";
      } catch (PDOException $e) {
        $taskError = "Failed to save task. Please try again.";
      }
    }
  }
}

$taskStats = [
  "total" => 0,
  "completed" => 0,
  "in_progress" => 0,
  "pending" => 0,
];
$dueTodayTasks = [];
$overdueTasks = [];

if ($userId > 0) {
  try {
    $statsStmt = $pdo->prepare(
      "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status IN ('pending', 'due') THEN 1 ELSE 0 END) AS pending
      FROM tasks
      WHERE user_id = ?"
    );
    $statsStmt->execute([$userId]);
    $statsRow = $statsStmt->fetch() ?: [];

    $taskStats["total"] = (int)($statsRow["total"] ?? 0);
    $taskStats["completed"] = (int)($statsRow["completed"] ?? 0);
    $taskStats["in_progress"] = (int)($statsRow["in_progress"] ?? 0);
    $taskStats["pending"] = (int)($statsRow["pending"] ?? 0);

    $dueTodayStmt = $pdo->prepare(
      "SELECT task_name, due_at
      FROM tasks
      WHERE user_id = ?
        AND due_at IS NOT NULL
        AND DATE(due_at) = CURDATE()
        AND status NOT IN ('completed', 'archive', 'archived')
      ORDER BY due_at ASC
      LIMIT 3"
    );
    $dueTodayStmt->execute([$userId]);
    $dueTodayTasks = $dueTodayStmt->fetchAll();

    $overdueStmt = $pdo->prepare(
      "SELECT task_name, due_at
      FROM tasks
      WHERE user_id = ?
        AND due_at IS NOT NULL
        AND due_at < NOW()
        AND status IN ('pending', 'due')
      ORDER BY due_at ASC
      LIMIT 3"
    );
    $overdueStmt->execute([$userId]);
    $overdueTasks = $overdueStmt->fetchAll();
  } catch (PDOException $e) {
    if ($taskError === "") {
      $taskError = "Unable to load task overview right now.";
    }
  }
}

$todayTimeInRaw = null;
$todayTimeOutRaw = null;

if ($userId > 0) {
  try {
    $todayAttendanceStmt = $pdo->prepare(
      "SELECT time_in, time_out
      FROM attendance
      WHERE user_id = ?
        AND DATE(time_in) = CURDATE()
      ORDER BY time_in DESC
      LIMIT 1"
    );
    $todayAttendanceStmt->execute([$userId]);
    $todayAttendanceRow = $todayAttendanceStmt->fetch() ?: [];

    $todayTimeInRaw = isset($todayAttendanceRow["time_in"]) ? (string)$todayAttendanceRow["time_in"] : null;
    $todayTimeOutRaw = isset($todayAttendanceRow["time_out"]) ? (string)$todayAttendanceRow["time_out"] : null;
  } catch (PDOException $e) {
    if ($attendanceError === "") {
      $attendanceError = "Unable to load today's attendance.";
    }
  }
}

$todayTimeInDisplay = "--:--";
$todayTimeOutDisplay = "--:--";
$todayStartIso = "";
$todayEndIso = "";
$initialTotalSeconds = 0;

if ($todayTimeInRaw !== null && $todayTimeInRaw !== "") {
  try {
    $tz = new DateTimeZone("Asia/Manila");
    $timeIn = new DateTimeImmutable($todayTimeInRaw, $tz);
    $todayTimeInDisplay = $timeIn->format("g:i A");
    $todayStartIso = $timeIn->format(DATE_ATOM);

    if ($todayTimeOutRaw !== null && $todayTimeOutRaw !== "") {
      $timeOut = new DateTimeImmutable($todayTimeOutRaw, $tz);
      $todayTimeOutDisplay = $timeOut->format("g:i A");
      $todayEndIso = $timeOut->format(DATE_ATOM);
      $initialTotalSeconds = max(0, $timeOut->getTimestamp() - $timeIn->getTimestamp());
    } else {
      $initialTotalSeconds = max(0, time() - $timeIn->getTimestamp());
    }
  } catch (Exception $e) {
    // Keep defaults.
  }
}

$hasTimedInToday = $todayTimeInRaw !== null && $todayTimeInRaw !== "";
$hasTimedOutToday = $todayTimeOutRaw !== null && $todayTimeOutRaw !== "";
$canTimeIn = !$hasTimedInToday;
$canTimeOut = $hasTimedInToday && !$hasTimedOutToday;

$timeInHint = "Tap to record your start time";
if (!$canTimeIn) {
  $timeInHint = "Time in already recorded for today";
}

$timeOutHint = "Tap to record your end time";
if (!$hasTimedInToday) {
  $timeOutHint = "Time in first to enable time out";
} elseif (!$canTimeOut) {
  $timeOutHint = "Time out already recorded for today";
}

function formatTotalHours(int $totalSeconds, bool $subtractLunchBreak = false): string {
  $netSeconds = $subtractLunchBreak ? max(0, $totalSeconds - 3600) : max(0, $totalSeconds);
  $hours = intdiv($netSeconds, 3600);
  $minutes = intdiv($netSeconds % 3600, 60);
  return sprintf("%dh %02dm", $hours, $minutes);
}

$fullName = (string)($_SESSION["full_name"] ?? "User");

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
    <title>iEtracker - Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="./resources/images/logo.svg" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="./styles/style.css" />
  </head>
  <body>
    <nav class="navbar navbar-expand-lg app-navbar">
      <div class="container-fluid app-navbar-inner">
        <a
          href="#"
          class="app-brand d-flex align-items-center gap-2 text-decoration-none">
          <img class="app-logo" src="./resources/images/logo.svg" alt="logo" />
          <div class="d-flex align-items-center lh-1">
            <span class="text-info fs-5 fw-bold text-decoration-none">iE</span
            ><span class="text-white fs-5 fw-bold text-decoration-none"
              >tracker</span
            >
          </div>
        </a>

        <div class="d-flex align-items-center gap-3 app-navbar-actions">
          <div class="d-flex align-items-center gap-2 app-navbar-tools">
            <button
              type="button"
              id="openModal"
              data-open-task-modal="true"
              class="btn btn-info btn-sm px-3 fw-medium text-white">
              <i class="bi bi-plus-lg"></i> Create
            </button>
           
            
            <div class="notif-wrapper position-relative">
              <button
                class="btn app-icon-btn"
                type="button"
                id="notifToggleBtn"
                aria-label="Notifications">
                <i class="bi bi-bell fs-5 text-white"></i>
              </button>

              <div class="notif-panel" id="notifPanel">
                <div class="notif-header">
                  <span class="notif-title">Notifications</span>
                  <div class="d-flex align-items-center gap-2">
                    <button class="notif-clear-btn" type="button" id="notifClearAll" disabled>
                      <i class="bi bi-trash"></i> Clear all
                    </button>
                    <button class="notif-close-btn" type="button" id="notifCloseBtn" aria-label="Close">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>

                <div class="notif-body" id="notifBody">
                  <div class="notif-empty">
                    <i class="bi bi-bell-slash notif-empty-icon"></i>
                    <p class="notif-empty-title">No notifications yet</p>
                    <p class="notif-empty-subtitle">When you get notifications, they'll show up here.</p>
                  </div>
                </div>

                <div class="notif-footer">
                  <a href="#" class="notif-view-all">View all notifications</a>
                </div>
              </div>
            </div>
            <button class="btn app-icon-btn" type="button" id="helpToggleBtn" aria-label="Help">
              <i class="bi bi-question-circle fs-5 text-white"></i>
            </button>
            <!-- <button
              class="btn app-icon-btn"
              type="button"
              aria-label="Settings">
              <i class="bi bi-gear fs-5 text-white"></i>
            </button> -->
          </div>
         <div class="dropdown app-profile">
  <button
    class="btn d-flex align-items-center gap-2 p-0 border-0 bg-transparent dropdown-toggle"
    type="button"
    id="profileDropdown"
    data-bs-toggle="dropdown"
    aria-expanded="false"
  >
    <span class="text-white small fw-medium"><?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?></span>
    <span class="rounded-pill bg-info text-dark fw-semibold px-2 py-1">
      <?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?>
    </span>
  </button>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
    <li>
      <a class="dropdown-item" href="./views/users/profile_settings.php">
        <i class="bi bi-gear me-2"></i>Profile Settings
      </a>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item text-danger" href="./views/authenticator/logout.php">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </a>
    </li>
  </ul>
</div>
        </div>
      </div>
    </nav>

    <aside class="sidebar d-flex flex-column p-3">
      <!-- Workspace Switcher Area -->
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
      <!-- Primary Navigation -->
      <div class="d-flex flex-column gap-1 mb-4">
        <!-- <small class="text-uppercase text-secondary fw-bold mb-2 px-2" style="font-size: 0.7rem; letter-spacing: 1px;">Main Menu</small> -->
        <a
          href="./index.php"
          class="nav-link active d-flex align-items-center gap-3 mt-3 text-info">
          <i class="bi bi-house-door"></i> Dashboard
        </a>
        <a
          href="./views/users/task_tracker.php"
          class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-check2-square"></i> Task Tracker
        </a>
        <a href="./views/users/attendance.php" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-person"></i> My Attendance
        </a>
        <a href="./views/users/profile_settings.php" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-gear"></i> Profile Settings
        </a>
      </div>

      <!-- Projects Section -->
      <!-- <div class="d-flex flex-column gap-1">
        <div
          class="d-flex align-items-center justify-content-between px-2 mb-2">
          <small
            class="text-uppercase text-secondary fw-bold"
            style="font-size: 0.7rem; letter-spacing: 1px"
            >Projects</small
          >
          <button class="btn btn-sm p-0 text-white">
            <i class="bi bi-plus-lg "></i>
          </button>
        </div>
        <a href="./views/users/project_ietracker.html" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-circle-fill fs-6 text-info"></i> Project iETracker
        </a>
        <a href="./views/users/project_marketing_site.html" class="nav-link d-flex align-items-center gap-3">
          <i class="bi bi-circle-fill fs-6 text-warning"></i> Marketing Site
        </a>
      </div> -->
    </aside>

    <main class="app-main">
<h1 class="text-white fw-semibold mb-1">Welcome, <?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>!</h1>      <p class="text-secondary mb-4"><?= date("l, F j, Y") ?></p>

      <?php if ($attendanceError !== ""): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($attendanceError, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>

      <?php if ($attendanceSuccess !== ""): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($attendanceSuccess, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>

      <?php if ($taskError !== ""): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($taskError, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>

      <?php if ($taskSuccess !== ""): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($taskSuccess, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>

      <h2 class="text-white fs-5 fw-semibold mb-3">Quick Actions</h2>

      <section class="row g-3 quick-actions-row">
        <div class="col-12 col-md-6 col-xl-4">
          <form method="POST" action="" class="h-100">
            <input type="hidden" name="attendance_action" value="time_in" />
            <button
              type="submit"
              class="content-card quick-action-card card h-100 text-light w-100 text-start border-0"
              <?= $canTimeIn ? "" : "disabled" ?>>
              <span class="card-body d-flex flex-column gap-2">
                <span class="d-flex align-items-center gap-2">
                  <span class="timeIn quick-action-icon text-success-emphasis">
                    <i class="bi bi-box-arrow-left text-success fs-5 icon-bold"></i>
                  </span>
                  <span class="fs-4 fw-semibold">Time In</span>
                </span>
                <span class="text-secondary"><?= htmlspecialchars($timeInHint, ENT_QUOTES, "UTF-8") ?></span>
              </span>
            </button>
          </form>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
          <form method="POST" action="" class="h-100">
            <input type="hidden" name="attendance_action" value="time_out" />
            <button
              type="submit"
              class="content-card quick-action-card card h-100 text-light w-100 text-start border-0"
              <?= $canTimeOut ? "" : "disabled" ?>>
              <span class="card-body d-flex flex-column gap-2">
                <span class="d-flex align-items-center gap-2">
                  <span class="timeOut quick-action-icon text-danger-emphasis">
                    <i class="bi bi-box-arrow-right text-danger fs-5 icon-bold"></i>
                  </span>
                  <span class="fs-4 fw-semibold">Time Out</span>
                </span>
                <span class="text-secondary"><?= htmlspecialchars($timeOutHint, ENT_QUOTES, "UTF-8") ?></span>
              </span>
            </button>
          </form>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
          <button
            type="button"
            id="openModalQuickAction"
            data-open-task-modal="true"
            class="content-card quick-action-card card h-100 text-light w-100 text-start border-0">
            <span class="card-body d-flex flex-column gap-2">
              <span class="d-flex align-items-center gap-2">
                <span class="addTask quick-action-icon text-danger icon-bold">
                  <i class="bi bi-plus-lg fs-5"></i>
                </span>
                <span class="fs-4 fw-semibold">Add Task</span>
              </span>
              <span class="text-secondary">Create a new task</span>
            </span>
          </button>
        </div>
      </section>

      <section class="content-card attendance-card text-light mt-4">
        <h3 class="fs-2 fw-semibold mb-4">Today's Attendance</h3>
        <div class="row g-3">
          <div class="col-12 col-md-4">
<p class="small text-secondary mb-1 d-flex align-items-center gap-2 attendance-meta">
  <span id="location" class="attendance-location"></span>
  <span class="vr border-info opacity-75" style="height: 17px"></span>
  <span id="day" class="attendance-day"></span>
</p>            <p class="attendance-value mb-0"><span id="time"></span></p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <p class="small text-secondary mb-1">Time In</p>
            <p class="attendance-value mb-0" id="todayTimeInValue"><?= htmlspecialchars($todayTimeInDisplay, ENT_QUOTES, "UTF-8") ?></p>
          </div>
          <div class="col-12 col-md-4">
            <p class="small text-secondary mb-1">Time Out</p>
            <p class="attendance-value mb-0" id="todayTimeOutValue"><?= htmlspecialchars($todayTimeOutDisplay, ENT_QUOTES, "UTF-8") ?></p>
          </div>
          <div class="col-12 col-md-4">
            <p class="small text-secondary mb-1">Total Hours</p>
            <p
              class="attendance-value mb-0"
              id="todayTotalHours"
              data-start-time="<?= htmlspecialchars($todayStartIso, ENT_QUOTES, "UTF-8") ?>"
              data-end-time="<?= htmlspecialchars($todayEndIso, ENT_QUOTES, "UTF-8") ?>"
              data-total-seconds="<?= (int)$initialTotalSeconds ?>"><?= htmlspecialchars(formatTotalHours($initialTotalSeconds, $hasTimedOutToday), ENT_QUOTES, "UTF-8") ?></p>
          </div>
        </div>
      </section>

      <section class="mt-4">
        <h3 class="text-white fs-3 fw-semibold mb-3">Task Overview</h3>
        <div class="row g-3">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="task-card task-total h-100">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="task-label">Total Tasks</span>
                <i class="bi bi-clock task-icon"></i>
              </div>
              <p class="task-value mb-0"><?= (int)$taskStats["total"] ?></p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="task-card task-completed h-100">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="task-label">Completed</span>
                <i class="bi bi-check-circle task-icon"></i>
              </div>
              <p class="task-value mb-0"><?= (int)$taskStats["completed"] ?></p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="task-card task-progress h-100">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="task-label">In Progress</span>
                <i class="bi bi-clock task-icon"></i>
              </div>
              <p class="task-value mb-0"><?= (int)$taskStats["in_progress"] ?></p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="task-card task-pending h-100">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="task-label">Pending</span>
                <i class="bi bi-exclamation-circle task-icon"></i>
              </div>
              <p class="task-value mb-0"><?= (int)$taskStats["pending"] ?></p>
            </div>
          </div>
        </div>
      </section>

      <section class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
          <div class="content-card due-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="text-white fs-4 fw-semibold mb-0">Due Today</h4>
              <span class="task-pill"><?= count($dueTodayTasks) ?> tasks</span>
            </div>
            <?php if ($dueTodayTasks === []): ?>
              <p class="text-secondary mb-0">Empty Task</p>
            <?php else: ?>
              <?php foreach ($dueTodayTasks as $task): ?>
                <p class="text-secondary mb-1"><?= htmlspecialchars((string)($task["task_name"] ?? "Untitled Task"), ENT_QUOTES, "UTF-8") ?></p>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="content-card due-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="text-white fs-4 fw-semibold mb-0">Overdue Tasks</h4>
              <span class="task-pill task-pill-danger"><?= count($overdueTasks) ?> tasks</span>
            </div>
            <?php if ($overdueTasks === []): ?>
              <p class="text-secondary mb-0">Empty Task</p>
            <?php else: ?>
              <?php foreach ($overdueTasks as $task): ?>
                <p class="text-secondary mb-1"><?= htmlspecialchars((string)($task["task_name"] ?? "Untitled Task"), ENT_QUOTES, "UTF-8") ?></p>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

<!-- Pop-ups Section-->
<section id="helpModal" class="help-modal">
  <div class="help-modal-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-semibold text-white mb-0">Help Center</h5>
      <i id="helpCloseBtn" class="bi bi-x text-white fs-4" style="cursor: pointer;"></i>
    </div>

    <p class="text-secondary small mb-4">Quick guides and shortcuts to help you get the most out of iEtracker.</p>

    <div class="help-section mb-3">
      <div class="help-section-title">
        <i class="bi bi-rocket-takeoff text-info"></i>
        <span>Getting Started</span>
      </div>
      <div class="help-item">
        <i class="bi bi-check-circle text-info"></i>
        <div>
          <span class="help-item-title">Create Tasks</span>
          <p class="help-item-desc">Click the <strong>+ Create</strong> button in the navbar to add a new task with a name, description, and due date.</p>
        </div>
      </div>
      <div class="help-item">
        <i class="bi bi-clock text-info"></i>
        <div>
          <span class="help-item-title">Track Attendance</span>
          <p class="help-item-desc">Use the <strong>Time In</strong> and <strong>Time Out</strong> actions on the dashboard to record your daily attendance.</p>
        </div>
      </div>
      <div class="help-item">
        <i class="bi bi-bell text-info"></i>
        <div>
          <span class="help-item-title">Notifications</span>
          <p class="help-item-desc">Click the bell icon to view your latest notifications and stay up to date.</p>
        </div>
      </div>
    </div>

    <div class="help-section mb-3">
      <div class="help-section-title">
        <i class="bi bi-keyboard text-info"></i>
        <span>Keyboard Shortcuts</span>
      </div>
      <div class="help-shortcut">
        <span class="text-secondary small">Close any modal / panel</span>
        <kbd>Esc</kbd>
      </div>
    </div>

    <div class="help-section">
      <div class="help-section-title">
        <i class="bi bi-envelope text-info"></i>
        <span>Need More Help?</span>
      </div>
      <p class="text-secondary small mb-0 ps-4">Reach out to your administrator or check the project documentation for more details.</p>
    </div>
  </div>
</section>

<section id="addModal" class="task-modal">
  <form method="POST" action="" class="task-form rounded-3 p-3">
    <input type="hidden" id="create_task" name="create_task" value="1">

    <div class="d-flex flex-row justify-content-between text-light mb-3">
      <p class="fw-semibold mb-0">New Task</p>
      <i id="closeModal" class="bi bi-x" style="cursor: pointer;"></i>
    </div>

    <div class="mb-3">
      <input
        type="text"
        name="task_name"
        placeholder="Task Name"
        class="input-task text-secondary border-0"
        required>
    </div>

    <div class="mb-3">
      <label for="task-description" class="text-secondary d-flex align-items-center gap-2 mb-2">
        Task Description
      </label>
      <textarea
        id="task-description"
        name="task_description"
        class="text-secondary border-0"
        rows="3"
        placeholder="What is the task about?"></textarea>

      <label for="due-date" class="text-secondary d-flex align-items-center gap-2 mb-2 mt-2">
        Due Date
      </label>
      <input type="date" id="due-date" name="due_date" class="input-calendar text-secondary" required>

      <label for="due-time" class="text-secondary d-flex align-items-center gap-2 mb-2 mt-2">
        Due Time
      </label>
      <input type="time" id="due-time" name="due_time" class="input-calendar text-secondary" required>
    </div>

    <div class="task-modal-footer">
      <span class="task-owner-badge"><?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?></span>
      <button type="submit" class="task-create-btn">Create task</button>
    </div>
  </form>
</section>
    </main>

      <script src="scripts/partial.js"></script>
      <script src="scripts/script.js"></script>
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
  crossorigin="anonymous"></script>
  </body>
</html>
