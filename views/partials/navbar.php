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
            <button
              type="button"
              id="openModal"
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
    <!-- <li>
      <a class="dropdown-item" href="./views/users/profile_settings.php">
        <i class="bi bi-gear me-2"></i>Profile Settings
      </a>
    </li>
    <li><hr class="dropdown-divider"></li> -->
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