<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: views/authenticator/login.php");
  exit;
}

$pdo = require __DIR__ . "/../../database/ietracker_database.php";
$userId = (int)($_SESSION["user_id"] ?? 0);

$taskError = "";
$taskSuccess = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_task_status"])) {
  $taskId = (int)($_POST["task_id"] ?? 0);
  $requestedStatus = strtolower(trim((string)($_POST["task_status"] ?? "")));

  $statusMap = [
    "in_progress" => "in_progress",
    "completed" => "completed",
    "archived" => "archive",
    "archive" => "archive",
  ];

  if ($taskId <= 0 || !isset($statusMap[$requestedStatus])) {
    $taskError = "Invalid task update request.";
  } else {
    try {
      $update = $pdo->prepare(
        "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ? LIMIT 1"
      );
      $update->execute([$statusMap[$requestedStatus], $taskId, $userId]);

      if ($update->rowCount() > 0) {
        $taskSuccess = "Task status updated.";
      } else {
        $taskError = "Task not found or status unchanged.";
      }
    } catch (PDOException $e) {
      $taskError = "Unable to update task status right now.";
    }
  }
}

$counts = [
  "total" => 0,
  "completed" => 0,
  "in_progress" => 0,
  "pending" => 0,
  "due" => 0,
  "archived" => 0,
];
$groupedTasks = [
  "pending" => [],
  "due" => [],
  "in_progress" => [],
  "completed" => [],
  "archived" => [],
];

try {
  $stmt = $pdo->prepare(
    "SELECT id, task_name, task_description, due_at, status
     FROM tasks
     WHERE user_id = ?
     ORDER BY CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at ASC, id DESC"
  );
  $stmt->execute([$userId]);
  $tasks = $stmt->fetchAll();
  $now = new DateTimeImmutable("now");

  foreach ($tasks as $task) {
    $status = strtolower(trim((string)($task["status"] ?? "pending")));
    if ($status === "archive" || $status === "archived") {
      $status = "archived";
    }

    if ($status === "pending") {
      $dueAtRaw = trim((string)($task["due_at"] ?? ""));
      if ($dueAtRaw !== "") {
        try {
          $dueAt = new DateTimeImmutable($dueAtRaw);
          if ($dueAt < $now) {
            $status = "due";
          }
        } catch (Exception $e) {
          // Ignore invalid due date and keep task in pending.
        }
      }
    }

    if (!isset($groupedTasks[$status])) {
      $status = "pending";
    }

    $groupedTasks[$status][] = $task;
  }

  $counts["pending"] = count($groupedTasks["pending"]);
  $counts["due"] = count($groupedTasks["due"]);
  $counts["in_progress"] = count($groupedTasks["in_progress"]);
  $counts["completed"] = count($groupedTasks["completed"]);
  $counts["archived"] = count($groupedTasks["archived"]);
  $counts["total"] = count($tasks);
} catch (PDOException $e) {
  $taskError = "Unable to load tasks right now.";
}

function formatDueLabel(?string $dueAt): string {
  if ($dueAt === null || trim($dueAt) === "") {
    return "No due date";
  }

  try {
    return (new DateTime($dueAt))->format("M d, Y h:i A");
  } catch (Exception $e) {
    return "Invalid due date";
  }
}

function formatDueCardDate(?string $dueAt): string {
  if ($dueAt === null || trim($dueAt) === "") {
    return "No date";
  }

  try {
    return (new DateTime($dueAt))->format("M d");
  } catch (Exception $e) {
    return "Invalid";
  }
}

function renderTaskCard(array $task, string $initials, string $status): void {
  $title = htmlspecialchars((string)($task["task_name"] ?? "Untitled Task"), ENT_QUOTES, "UTF-8");
  $descriptionRaw = trim((string)($task["task_description"] ?? ""));
  $description = htmlspecialchars($descriptionRaw !== "" ? $descriptionRaw : "No description", ENT_QUOTES, "UTF-8");
  $dueDate = htmlspecialchars(formatDueCardDate((string)($task["due_at"] ?? "")), ENT_QUOTES, "UTF-8");
  $dueFull = htmlspecialchars(formatDueLabel((string)($task["due_at"] ?? "")), ENT_QUOTES, "UTF-8");
  $ownerInitials = htmlspecialchars($initials, ENT_QUOTES, "UTF-8");
  $safeStatus = htmlspecialchars($status, ENT_QUOTES, "UTF-8");
  $safeTaskId = (int)($task["id"] ?? 0);
  $safeDescriptionRaw = htmlspecialchars($descriptionRaw !== "" ? $descriptionRaw : "No description", ENT_QUOTES, "UTF-8");
  $dbStatus = strtolower(trim((string)($task["status"] ?? "pending")));
  if ($dbStatus === "archive") {
    $dbStatus = "archived";
  }
  $safeDbStatus = htmlspecialchars($dbStatus, ENT_QUOTES, "UTF-8");

  echo '<article class="board-task board-task-' . $safeStatus . '"';
  echo ' role="button" tabindex="0"';
  echo ' data-task-id="' . $safeTaskId . '"';
  echo ' data-task-title="' . $title . '"';
  echo ' data-task-description="' . $safeDescriptionRaw . '"';
  echo ' data-task-due="' . $dueFull . '"';
  echo ' data-task-status="' . $safeDbStatus . '"';
  echo '>';
  echo '<p class="board-task-title">' . $title . '</p>';
  echo '<span class="board-task-status">' . strtoupper(str_replace("_", " ", $safeDbStatus)) . '</span>';
  echo '<p class="board-task-desc">' . $description . '</p>';
  echo '<div class="board-task-divider"></div>';
  echo '<div class="board-task-footer">';
  echo '<span class="board-task-avatar">' . $ownerInitials . '</span>';
  echo '<span class="board-task-date"><i class="bi bi-clock-history"></i> ' . $dueDate . '</span>';
  echo '</div>';
  echo '</article>';
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
    <link rel="icon" type="image/svg+xml" href="/resources/images/logo.svg" />
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
    <!-- <nav class="navbar navbar-expand-lg app-navbar">
      <div class="container-fluid app-navbar-inner">
        <a
          href="#"
          class="app-brand d-flex align-items-center gap-2 text-decoration-none">
           <img
            class="app-logo"
            src="../../resources/images/logo.svg"
            alt="logo" />
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
              class="btn btn-info btn-sm px-3 fw-medium text-white">
              <i class="bi bi-plus-lg"></i> Create
            </button>
            <button
              class="btn app-icon-btn"
              type="button"
              aria-label="Notifications">
              <i class="bi bi-bell fs-5 text-white"></i>
            </button>
            <button class="btn app-icon-btn" type="button" aria-label="Help">
              <i class="bi bi-question-circle fs-5 text-white"></i>
            </button>
            <button
              class="btn app-icon-btn"
              type="button"
              aria-label="Settings">
              <i class="bi bi-gear fs-5 text-white"></i>
            </button>
          </div>
          <div class="d-flex align-items-center gap-2 app-profile">
            <span class="text-white small fw-medium"><?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?></span>
            <span class="rounded-pill bg-info text-dark fw-semibold px-2 py-1"
              ><?= htmlspecialchars($initials, ENT_QUOTES, "UTF-8") ?></span
            >
          </div>
        </div>
      </div>
    </nav> -->

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
          class="nav-link active d-flex align-items-center gap-3 text-info">
          <i class="bi bi-check2-square"></i> Task Tracker
        </a>
        <a
          href="./attendance.php"
          class="nav-link d-flex align-items-center gap-3">
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

    <main class="app-main">
      <?php if ($taskError !== ""): ?>
        <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($taskError, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>
      <?php if ($taskSuccess !== ""): ?>
        <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($taskSuccess, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>

      <section class="task-list-shell">
        <div
          class="d-flex flex-wrap align-items-center justify-content-between gap-2 task-list-toolbar">
          <div class="d-flex align-items-center gap-2">
            <button
              type="button"
              class="btn btn-sm task-view-btn d-inline-flex align-items-center gap-2"
              aria-pressed="true">
              <!-- <a
                href="./task_tracker.php"
                class="text-decoration-none text-reset"
                ><i class="bi bi-list"></i> <span>List</span></a
              > -->
            </button>
            <!-- <a
              href="./board.php"
              class=" btn btn-sm task-view-btn d-inline-flex align-items-center gap-2">
              <i class="bi bi-grid"></i>
              <span>Board</span>
            </a> -->
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2 task-stats">
            <span class="badge text-bg-dark task-stat-pill border-blue">
              <i class="bi bi-list-task"></i>
              Total: <?= (int)$counts["total"] ?>
            </span>
            <span class="badge text-bg-dark task-stat-pill border-green">
              <i class="bi bi-check-circle"></i>
              Completed: <?= (int)$counts["completed"] ?>
            </span>
            <span class="badge text-bg-dark task-stat-pill border-sky">
              <i class="bi bi-hourglass-split"></i>
              In Progress: <?= (int)$counts["in_progress"] ?>
            </span>
            <span class="badge text-bg-dark task-stat-pill border-orange">
              <i class="bi bi-exclamation-triangle"></i>
              Due: <?= (int)$counts["due"] ?>
            </span>
          </div>
        </div>

        <div class="board-view">
          <div class="board-toolbar">
            <h2 class="board-title">Board View</h2>
            <!-- <input class="board-search" type="text" placeholder="" /> -->
          </div>

          <div class="board-columns">
            <div class="board-column">
              <div class="board-column-header">
                <span class="board-dot todo"></span>
                <span>To Do</span>
                <span class="board-count"><?= (int)$counts["pending"] ?></span>
              </div>
              <div class="board-column-line"></div>
              <div class="board-column-body board-column-body-pending">
                <?php if ($groupedTasks["pending"] === []): ?>
                  <p class="board-task-empty">No tasks</p>
                <?php else: ?>
                  <?php foreach ($groupedTasks["pending"] as $task): ?>
                    <?php renderTaskCard($task, $initials, "pending"); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>


            <div class="board-column">
              <div class="board-column-header">
                <span class="board-dot progress"></span>
                <span>In Progress</span>
                <span class="board-count"><?= (int)$counts["in_progress"] ?></span>
              </div>
              <div class="board-column-line"></div>
              <div class="board-column-body board-column-body-in-progress">
                <?php if ($groupedTasks["in_progress"] === []): ?>
                  <p class="board-task-empty">No tasks</p>
                <?php else: ?>
                  <?php foreach ($groupedTasks["in_progress"] as $task): ?>
                    <?php renderTaskCard($task, $initials, "in-progress"); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="board-column">
              <div class="board-column-header">
                <span class="board-dot done"></span>
                <span>Done</span>
                <span class="board-count"><?= (int)$counts["completed"] ?></span>
              </div>
              <div class="board-column-line"></div>
              <div class="board-column-body board-column-body-completed">
                <?php if ($groupedTasks["completed"] === []): ?>
                  <p class="board-task-empty">No tasks</p>
                <?php else: ?>
                  <?php foreach ($groupedTasks["completed"] as $task): ?>
                    <?php renderTaskCard($task, $initials, "completed"); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>



            <div class="board-column">
              <div class="board-column-header">
                <span class="board-dot due"></span>
                <span>Due</span>
                <span class="board-count"><?= (int)$counts["due"] ?></span>
              </div>
              <div class="board-column-line"></div>
              <div class="board-column-body board-column-body-due">
                <?php if ($groupedTasks["due"] === []): ?>
                  <p class="board-task-empty">No tasks</p>
                <?php else: ?>
                  <?php foreach ($groupedTasks["due"] as $task): ?>
                    <?php renderTaskCard($task, $initials, "due"); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

          
            <div class="board-column">
              <div class="board-column-header">
                <span class="board-dot todo"></span>
                <span>Archive</span>
                <span class="board-count"><?= (int)$counts["archived"] ?></span>
              </div>
              <div class="board-column-line"></div>
              <div class="board-column-body board-column-body-archived">
                <?php if ($groupedTasks["archived"] === []): ?>
                  <p class="board-task-empty">No tasks</p>
                <?php else: ?>
                  <?php foreach ($groupedTasks["archived"] as $task): ?>
                    <?php renderTaskCard($task, $initials, "archived"); ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content task-detail-modal">
          <div class="modal-header border-0 pb-2">
            <h5 class="modal-title text-white" id="taskDetailModalLabel">Task Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-1">
            <h6 class="task-detail-title mb-2" id="taskModalTitle">Untitled Task</h6>
            <p class="task-detail-description mb-3" id="taskModalDescription">No description</p>
            <div class="task-detail-meta mb-3">
              <span><i class="bi bi-clock-history"></i> <span id="taskModalDue">No due date</span></span>
              <span class="task-detail-status" id="taskModalStatus">PENDING</span>
            </div>

            <form method="POST" action="" class="d-flex flex-wrap gap-2">
              <input type="hidden" name="update_task_status" value="1">
              <input type="hidden" name="task_id" id="taskModalTaskId" value="0">
              <button type="submit" name="task_status" value="in_progress" class="btn btn-sm task-action-btn task-action-progress">In Progress</button>
              <button type="submit" name="task_status" value="completed" class="btn btn-sm task-action-btn task-action-done">Done</button>
              <button type="submit" name="task_status" value="archived" class="btn btn-sm task-action-btn task-action-archive">Archive</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php require __DIR__ . "/../partials/help_modal.php"; ?>

    <script src="../../scripts/partial.js"></script>
    <script src="scripts/task.js"></script>
   <script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
  crossorigin="anonymous"></script>
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const modalElement = document.getElementById("taskDetailModal");
        if (!modalElement || typeof bootstrap === "undefined") return;

        const modal = new bootstrap.Modal(modalElement);
        const taskIdInput = document.getElementById("taskModalTaskId");
        const titleEl = document.getElementById("taskModalTitle");
        const descEl = document.getElementById("taskModalDescription");
        const dueEl = document.getElementById("taskModalDue");
        const statusEl = document.getElementById("taskModalStatus");

        const taskCards = document.querySelectorAll(".board-task");

        const openTaskModal = (card) => {
          const taskId = card.getAttribute("data-task-id") || "0";
          const title = card.getAttribute("data-task-title") || "Untitled Task";
          const description = card.getAttribute("data-task-description") || "No description";
          const due = card.getAttribute("data-task-due") || "No due date";
          const status = (card.getAttribute("data-task-status") || "pending").toUpperCase().replace("_", " ");

          if (taskIdInput) taskIdInput.value = taskId;
          if (titleEl) titleEl.textContent = title;
          if (descEl) descEl.textContent = description;
          if (dueEl) dueEl.textContent = due;
          if (statusEl) statusEl.textContent = status;

          modal.show();
        };

        taskCards.forEach((card) => {
          card.addEventListener("click", () => openTaskModal(card));
          card.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
              event.preventDefault();
              openTaskModal(card);
            }
          });
        });
      });
    </script>
  </body>
</html>
