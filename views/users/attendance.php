<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: views/authenticator/login.php");
  exit;
}

date_default_timezone_set("Asia/Manila");

$pdo = require __DIR__ . "/../../database/ietracker_database.php";
$userId = (int)($_SESSION["user_id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_days_absent"])) {
  header("Content-Type: application/json; charset=UTF-8");

  if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
  }

  $daysAbsentDb = 0;

if ($userId > 0) {
  $stmt = $pdo->prepare("SELECT days_absent FROM users WHERE id = ? LIMIT 1");
  $stmt->execute([$userId]);
  $daysAbsentDb = (int)($stmt->fetchColumn() ?? 0);
}

  $daysAbsentRaw = $_POST["days_absent"] ?? null;
  $daysAbsent = filter_var(
    $daysAbsentRaw,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 0, "max_range" => 366]]
  );

  if ($daysAbsent === false) {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "Invalid days_absent"]);
    exit;
  }

  try {
    $stmt = $pdo->prepare("UPDATE users SET days_absent = ? WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$daysAbsent, $userId]);
    echo json_encode(["ok" => true]);
    exit;
  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Unable to save days_absent"]);
    exit;
  }
}

$fullName = (string)($_SESSION["full_name"] ?? "User");

$parts = preg_split('/\s+/', trim($fullName));
$initials = "";
foreach (array_slice($parts ?: [], 0, 2) as $p) {
  $initials .= strtoupper(substr($p, 0, 1));
}
if ($initials === "") $initials = "U";

$attendanceRows = [];

if ($userId > 0) {
  try {
    $attendanceStmt = $pdo->prepare(
      "SELECT time_in, time_out
       FROM attendance
       WHERE user_id = ?
         AND time_in IS NOT NULL
       ORDER BY time_in DESC"
    );
    $attendanceStmt->execute([$userId]);
    $rawRows = $attendanceStmt->fetchAll();

    $attendanceByDate = [];

    foreach ($rawRows as $row) {
      $timeInRaw = (string)($row["time_in"] ?? "");
      if ($timeInRaw === "") {
        continue;
      }

      try {
        $timeIn = new DateTimeImmutable($timeInRaw, new DateTimeZone("Asia/Manila"));
      } catch (Exception $e) {
        continue;
      }

      $dateKey = $timeIn->format("Y-m-d");
      $timeOutRaw = isset($row["time_out"]) ? (string)$row["time_out"] : "";
      $timeOut = null;
      if ($timeOutRaw !== "") {
        try {
          $timeOut = new DateTimeImmutable($timeOutRaw, new DateTimeZone("Asia/Manila"));
        } catch (Exception $e) {
          $timeOut = null;
        }
      }

      if (!isset($attendanceByDate[$dateKey])) {
        $attendanceByDate[$dateKey] = [
          "first_in" => $timeIn,
          "last_out" => $timeOut,
          "work_seconds" => 0,
        ];
      } else {
        if ($timeIn < $attendanceByDate[$dateKey]["first_in"]) {
          $attendanceByDate[$dateKey]["first_in"] = $timeIn;
        }

        if (
          $timeOut !== null
          && (
            $attendanceByDate[$dateKey]["last_out"] === null
            || $timeOut > $attendanceByDate[$dateKey]["last_out"]
          )
        ) {
          $attendanceByDate[$dateKey]["last_out"] = $timeOut;
        }
      }

      if ($timeOut !== null) {
        $diffSeconds = max(0, $timeOut->getTimestamp() - $timeIn->getTimestamp());
        $attendanceByDate[$dateKey]["work_seconds"] += $diffSeconds;
      }
    }

    foreach ($attendanceByDate as $dateKey => $dayData) {
      $firstIn = $dayData["first_in"];
      $lastOut = $dayData["last_out"];
      $workSeconds = (int)$dayData["work_seconds"];
      $weekdayNumber = (int)$firstIn->format("N");
      $isWeekday = $weekdayNumber >= 1 && $weekdayNumber <= 5;
      $timeInMinutes = ((int)$firstIn->format("H") * 60) + (int)$firstIn->format("i");
      $timeOutMinutes = $lastOut !== null
        ? (((int)$lastOut->format("H") * 60) + (int)$lastOut->format("i"))
        : -1;

      $qualifiesPresent = $isWeekday && $timeInMinutes <= 540 && $timeOutMinutes >= 1080;

      $workSeconds = 0;
      if ($lastOut !== null) {
        $workSeconds = max(0, (int)$dayData["work_seconds"]);
        $workSeconds = max(0, $workSeconds - 3600);
      }

      $attendanceRows[] = [
        "date_key" => $dateKey,
        "year" => (int)$firstIn->format("Y"),
        "month" => (int)$firstIn->format("n"),
        "day_number" => $firstIn->format("j"),
        "day_short" => $firstIn->format("D"),
        "time_in" => $firstIn->format("H:i"),
        "time_out" => $lastOut !== null ? $lastOut->format("H:i") : "--:--",
        "work_hours" => $workSeconds > 0 ? number_format($workSeconds / 3600, 1) . "h" : "--",
        "work_seconds" => $workSeconds,
        "is_weekday" => $isWeekday,
        "qualifies_present" => $qualifiesPresent,
        "status" => $qualifiesPresent ? "present" : "absent",
      ];
    }

    usort(
      $attendanceRows,
      function (array $a, array $b): int {
        return strcmp((string)$b["date_key"], (string)$a["date_key"]);
      }
    );
  } catch (PDOException $e) {
    $attendanceRows = [];
  }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>iEtracker - Dashboard</title>
    <link
      rel="icon"
      type="image/svg+xml"
      href="../../../resources/images/logo.svg" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="../../styles/style.css" />
    <link rel="stylesheet" href="../../styles/task.css" />
  </head>
  <body>
   
    <?php
$basePath = "../../";
require __DIR__ . "/../partials/navbar.php";
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

        <!-- <i class="bi bi-caret-down-fill text-secondary"></i> -->
      </div>


      <span
        class="border border-bottom-0 border-info"
        style="--bs-border-opacity: 0.1"></span>
      <!-- Primary Navigation -->
      <div class="d-flex flex-column gap-1 mb-4">
        <!-- <small class="text-uppercase text-secondary fw-bold mb-2 px-2" style="font-size: 0.7rem; letter-spacing: 1px;">Main Menu</small> -->
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
          class="nav-link active d-flex align-items-center gap-3 text-info">
          <i class="bi bi-person"></i> My Attendance
        </a>
        <a
          href="./profile_settings.php"
          class="nav-link d-flex align-items-center gap-3">
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

    <main class="app-main attendance-page">
      <section class="attendance-shell">
        <header class="attendance-topbar">
          <h1 class="attendance-heading">My Attendance</h1>
          <div class="attendance-month-nav">
            <button
              type="button"
              class="month-arrow"
              id="prevMonthBtn"
              aria-label="Previous month">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="month-chip" aria-label="Current month">
              <i class="bi bi-calendar-event"></i>
              <span id="monthLabel">March 2026</span>
            </button>
            <button
              type="button"
              class="month-arrow"
              id="nextMonthBtn"
              aria-label="Next month">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </header>

        <div class="attendance-metrics" aria-label="Attendance summary">
          <article class="metric-card">
            <span class="metric-icon success"
              ><i class="bi bi-check-circle"></i
            ></span>
            <div>
              <p class="metric-label">Days Present</p>
<p class="metric-value" id="daysPresentValue" data-source="db"><?= $daysPresentDb ?></p>
            </div>
          </article>
          <article class="metric-card">
            <span class="metric-icon danger"
              ><i class="bi bi-x-circle"></i
            ></span>
            <div>
              <p class="metric-label">Days Absent</p>
<p class="metric-value" id="daysAbsentValue"><?= $daysAbsentDb ?></p>            </div>
          </article>
          <!-- <article class="metric-card">
            <span class="metric-icon leave"
              ><i class="bi bi-umbrella"></i
            ></span>
            <div>
              <p class="metric-label">On Leave</p>
              <p class="metric-value">0</p>
            </div>
          </article> -->
          <!-- <article class="metric-card">
            <span class="metric-icon trend"
              ><i class="bi bi-graph-up-arrow"></i
            ></span>
            <div>
              <p class="metric-label">Attendance Rate</p>
              <p class="metric-value">0%</p>
            </div>
          </article> -->
          <article class="metric-card">
            <span class="metric-icon info"><i class="bi bi-clock"></i></span>
            <div>
              <p class="metric-label">Total Hours</p>
              <p class="metric-value" id="totalWorkHours">0.0h</p>
            </div>
          </article>
        </div>

        <div class="attendance-list" aria-label="Attendance entries">
          <?php foreach ($attendanceRows as $row): ?>
            <article
              class="attendance-row"
              data-date="<?= htmlspecialchars((string)$row["date_key"], ENT_QUOTES, "UTF-8") ?>"
              data-year="<?= (int)$row["year"] ?>"
              data-month="<?= (int)$row["month"] ?>"
              data-weekday="<?= (int)($row["is_weekday"] ? 1 : 0) ?>"
              data-qualifies-present="<?= (int)($row["qualifies_present"] ? 1 : 0) ?>"
              data-status="<?= htmlspecialchars((string)$row["status"], ENT_QUOTES, "UTF-8") ?>"
              data-work-seconds="<?= (int)$row["work_seconds"] ?>">
              <div class="row-date">
                <p class="date-number"><?= htmlspecialchars((string)$row["day_number"], ENT_QUOTES, "UTF-8") ?></p>
                <p class="date-day"><?= htmlspecialchars((string)$row["day_short"], ENT_QUOTES, "UTF-8") ?></p>
              </div>

              <span class="status-chip <?= ($row["status"] === "present") ? "present" : "absent" ?>">
                <i class="bi bi-circle" style="font-size: 0.45rem"></i>
                <?= ($row["status"] === "present") ? "Present" : "Absent" ?>
              </span>

              <div>
                <p class="row-metric-label">
                  <i class="bi bi-box-arrow-in-right"></i>
                  Time In
                </p>
                <p class="row-metric-value"><?= htmlspecialchars((string)$row["time_in"], ENT_QUOTES, "UTF-8") ?></p>
              </div>

              <div>
                <p class="row-metric-label out">
                  <i class="bi bi-box-arrow-right"></i>
                  Time Out
                </p>
                <p class="row-metric-value"><?= htmlspecialchars((string)$row["time_out"], ENT_QUOTES, "UTF-8") ?></p>
              </div>

              <div>
                <p class="row-metric-label hours">
                  <i class="bi bi-clock"></i>
                  Work Hours
                </p>
                <p class="row-metric-value"><?= htmlspecialchars((string)$row["work_hours"], ENT_QUOTES, "UTF-8") ?></p>
              </div>
            </article>
          <?php endforeach; ?>

          <article class="attendance-empty" id="attendanceEmptyState">
            <i class="bi bi-calendar2-x attendance-empty-icon"></i>
            <h2 class="attendance-empty-title">No attendance records yet</h2>
            <p class="attendance-empty-copy">
              Your attendance entries for this month will appear here once data
              is loaded.
            </p>
          </article>
        </div>

        <footer class="attendance-footer">
          <div class="legend-wrap">
            <span class="legend-title">Status Legend:</span>
            <span class="legend-item"><i class="dot present"></i> Present</span>
            <span class="legend-item"><i class="dot absent"></i> Absent</span>
            <span class="legend-item"><i class="dot leave"></i> Leave</span>
            <span class="legend-item"><i class="dot holiday"></i> Holiday</span>
            <span class="legend-item"
              ><i class="dot halfday"></i> Half Day</span
            >
          </div>
          <p class="current-time" id="currentTime">Current Time: --:--</p>
        </footer>
      </section>
    </main>

    <?php require __DIR__ . "/../partials/help_modal.php"; ?>

    <script src="../../scripts/partial.js"></script>
    <script src="../../scripts/attendance.js"></script>
   <script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
  crossorigin="anonymous"></script>
  </body>
</html>
