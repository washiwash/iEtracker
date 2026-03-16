<?php
declare(strict_types=1);

$basePath = $basePath ?? "./";
$fullNameSafe = htmlspecialchars((string)($fullName ?? "User"), ENT_QUOTES, "UTF-8");
$initialsSafe = htmlspecialchars((string)($initials ?? "U"), ENT_QUOTES, "UTF-8");
?>

<nav class="navbar navbar-expand-lg app-navbar">
      <div class="container-fluid app-navbar-inner">
        <a
          href="#"
          class="app-brand d-flex align-items-center gap-2 text-decoration-none">
          <img class="app-logo" src="<?= $basePath ?>resources/images/logo.svg" alt="logo" />
          <div class="d-flex align-items-center lh-1">
            <span class="text-info fs-5 fw-bold text-decoration-none">iE</span
            ><span class="text-white fs-5 fw-bold text-decoration-none"
              >tracker</span
            >
          </div>
        </a>

        <div class="d-flex align-items-center gap-3 app-navbar-actions">
          <div class="d-flex align-items-center gap-2 app-navbar-tools">
            <!-- <button
              type="button"
              id="openModal"
              data-open-task-modal="true"
              class="btn btn-info btn-sm px-3 fw-medium text-white">
              <i class="bi bi-plus-lg"></i> Create
            </button> -->
           
            
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
    <span class="text-white small fw-medium"><?= $fullNameSafe ?></span>
    <span class="rounded-pill bg-info text-dark fw-semibold px-2 py-1">
      <?= $initialsSafe ?>
    </span>
  </button>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
    <!-- <li>
      <a class="dropdown-item" href="./views/users/profile_settings.php">
        <i class="bi bi-gear me-2"></i>Profile Settings
      </a>
    </li>
    <li><hr class="dropdown-divider"></li> -->
    <li>
      <a class="dropdown-item text-danger" href="<?= $basePath ?>views/authenticator/logout.php">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </a>
    </li>
  </ul>
</div>
        </div>
      </div>
    </nav>
