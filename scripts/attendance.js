(function () {
  const monthLabel = document.getElementById("monthLabel");
  const prevMonthBtn = document.getElementById("prevMonthBtn");
  const nextMonthBtn = document.getElementById("nextMonthBtn");
  const currentTime = document.getElementById("currentTime");
  const list = document.querySelector(".attendance-list");
  const emptyState = document.getElementById("attendanceEmptyState");
  const daysPresentValue = document.getElementById("daysPresentValue");
  const daysAbsentValue = document.getElementById("daysAbsentValue");
  const totalHoursValue = document.getElementById("totalWorkHours");
  const rows = Array.from(document.querySelectorAll(".attendance-row"));

  let lastSavedAbsentKey = "";

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

    syncRowsByMonth();
    syncMetrics();
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

    const hasRows = rows.some((row) => row.style.display !== "none");
    emptyState.style.display = hasRows ? "none" : "flex";
  }

  function syncRowsByMonth() {
    const targetYear = viewDate.getFullYear();
    const targetMonth = viewDate.getMonth() + 1;

    rows.forEach((row) => {
      const rowYear = Number.parseInt(row.dataset.year || "0", 10);
      const rowMonth = Number.parseInt(row.dataset.month || "0", 10);
      const isVisible = rowYear === targetYear && rowMonth === targetMonth;
      row.style.display = isVisible ? "grid" : "none";
    });

    syncEmptyState();
  }

  function getVisibleRows() {
    return rows.filter((row) => row.style.display !== "none");
  }

  function totalWorkHours() {
    const totalSeconds = rows.reduce((acc, row) => {
      const seconds = Number.parseInt(row.dataset.workSeconds || "0", 10);
      return acc + (Number.isFinite(seconds) ? Math.max(0, seconds) : 0);
    }, 0);

    return totalSeconds / 3600;
  }

  function totalHours() {
    const today = new Date();
    const todayKey = [
      today.getFullYear(),
      String(today.getMonth() + 1).padStart(2, "0"),
      String(today.getDate()).padStart(2, "0"),
    ].join("-");

    const todaySeconds = rows.reduce((acc, row) => {
      const rowDate = String(row.dataset.date || "");
      if (rowDate !== todayKey) {
        return acc;
      }

      const seconds = Number.parseInt(row.dataset.workSeconds || "0", 10);
      return acc + (Number.isFinite(seconds) ? Math.max(0, seconds) : 0);
    }, 0);

    return todaySeconds / 3600;
  }

  function workingDaysForViewDate(startDay = 1) {
    const year = viewDate.getFullYear();
    const month = viewDate.getMonth();
    const today = new Date();
    const isCurrentMonth =
      today.getFullYear() === year && today.getMonth() === month;
    const monthLastDay = new Date(year, month + 1, 0).getDate();
    const dayLimit = isCurrentMonth ? today.getDate() : monthLastDay;

    let weekdays = 0;
    for (let day = startDay; day <= dayLimit; day += 1) {
      const date = new Date(year, month, day);
      const weekDay = date.getDay();
      if (weekDay !== 0 && weekDay !== 6) {
        weekdays += 1;
      }
    }

    return weekdays;
  }

  function isViewingCurrentMonth() {
    const today = new Date();
    return (
      today.getFullYear() === viewDate.getFullYear() &&
      today.getMonth() === viewDate.getMonth()
    );
  }

  function persistAbsentDays(absentDays) {
    if (!isViewingCurrentMonth()) {
      return;
    }

    const key = `${viewDate.getFullYear()}-${viewDate.getMonth() + 1}-${absentDays}`;
    if (key === lastSavedAbsentKey) {
      return;
    }

    lastSavedAbsentKey = key;

    const body = new URLSearchParams();
    body.set("save_days_absent", "1");
    body.set("days_absent", String(absentDays));

    window
      .fetch(window.location.href, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
        },
        body: body.toString(),
        credentials: "same-origin",
      })
      .catch(function () {
        // Best-effort persistence; UI should still work.
      });
  }

  function syncMetrics() {
    const visibleRows = getVisibleRows();
    const presentDays = visibleRows.filter((row) => {
      const isWeekday = Number.parseInt(row.dataset.weekday || "0", 10) === 1;
      const qualifiesPresent =
        Number.parseInt(row.dataset.qualifiesPresent || "0", 10) === 1;

      return isWeekday && qualifiesPresent;
    }).length;

    const lifetimeHours = totalWorkHours();

    let absentDays = 0;
    if (visibleRows.length > 0) {
      const visibleDates = visibleRows
        .map((row) => String(row.dataset.date || ""))
        .filter(Boolean)
        .map((date) => parseInt(date.split("-")[2] || "0", 10));
      
      const startDay = visibleDates.length > 0 ? Math.min(...visibleDates) : 1;
      absentDays = Math.max(0, workingDaysForViewDate(startDay) - presentDays);
    }

    if (daysPresentValue) {
      daysPresentValue.textContent = String(presentDays);
    }

    if (daysAbsentValue) {
      daysAbsentValue.textContent = String(absentDays);
    }

    if (totalHoursValue) {
      totalHoursValue.textContent = `${lifetimeHours.toFixed(1)}h`;
    }

    if (visibleRows.length > 0) {
      persistAbsentDays(absentDays);
    }
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

  window.totalHours = totalHours;

  renderMonth();
  updateClock();
  syncRowsByMonth();
  syncMetrics();

  // Keep the footer clock live.
  window.setInterval(updateClock, 1000);
})();
