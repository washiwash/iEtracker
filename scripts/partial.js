document.addEventListener("DOMContentLoaded", function () {
  // ─── Notification Panel ───
  const toggleBtn = document.getElementById("notifToggleBtn");
  const panel = document.getElementById("notifPanel");
  const closeBtn = document.getElementById("notifCloseBtn");

  if (toggleBtn && panel) {
    toggleBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      panel.classList.toggle("open");
    });

    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        panel.classList.remove("open");
      });
    }

    document.addEventListener("click", function (e) {
      if (!panel.contains(e.target) && !toggleBtn.contains(e.target)) {
        panel.classList.remove("open");
      }
    });
  }

  // ─── Help Modal ───
  const helpBtn = document.getElementById("helpToggleBtn");
  const helpModal = document.getElementById("helpModal");
  const helpCloseBtn = document.getElementById("helpCloseBtn");

  if (helpBtn && helpModal) {
    helpBtn.addEventListener("click", function () {
      helpModal.classList.add("open");
    });

    if (helpCloseBtn) {
      helpCloseBtn.addEventListener("click", function () {
        helpModal.classList.remove("open");
      });
    }

    helpModal.addEventListener("click", function (e) {
      if (e.target === helpModal) {
        helpModal.classList.remove("open");
      }
    });
  }

  // ─── Shared: Escape key closes panels/modals ───
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (panel) panel.classList.remove("open");
      if (helpModal) helpModal.classList.remove("open");
    }
  });
});
