(function () {
  const monthLabel = document.getElementById("monthLabel");
  const prevMonthBtn = document.getElementById("prevMonthBtn");
  const nextMonthBtn = document.getElementById("nextMonthBtn");
  const currentTime = document.getElementById("currentTime");
  const list = document.querySelector(".attendance-list");
  const emptyState = document.getElementById("attendanceEmptyState");

  // Month navigation state is anchored to today's month.
  const viewDate = new Date();
  viewDate.setDate(1);

  function renderMonth() {
    if (!monthLabel) {
      return;
    }

    monthLabel.textContent = viewDate.toLocaleString("en-US", {
      month: "long",
      year: "numeric",
    });
  }

  function updateClock() {
    if (!currentTime) {
      return;
    }

    const now = new Date();
    const timeText = now.toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    });

    currentTime.textContent = "Current Time: " + timeText;
  }

  function syncEmptyState() {
    if (!list || !emptyState) {
      return;
    }

    const hasRows = list.querySelectorAll(".attendance-row").length > 0;
    emptyState.style.display = hasRows ? "none" : "flex";
  }

  if (prevMonthBtn) {
    prevMonthBtn.addEventListener("click", function () {
      viewDate.setMonth(viewDate.getMonth() - 1);
      renderMonth();
    });
  }

  if (nextMonthBtn) {
    nextMonthBtn.addEventListener("click", function () {
      viewDate.setMonth(viewDate.getMonth() + 1);
      renderMonth();
    });
  }

  renderMonth();
  updateClock();
  syncEmptyState();

  // Keep the footer clock live.
  window.setInterval(updateClock, 1000);
})();
