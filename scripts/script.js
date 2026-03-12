function updateTime() {
  const now = new Date();
  const timeZone = "Asia/Manila";
  const time = new Intl.DateTimeFormat("en-US", {
    timeZone,
    hour: "numeric",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  }).format(now);
  const day = new Intl.DateTimeFormat("en-US", {
    timeZone,
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(now);

  const timeElement = document.getElementById("time");
  const dayElement = document.getElementById("day");

  if (timeElement) {
    timeElement.textContent = ` ${time}`;
  }

  if (dayElement) {
    dayElement.textContent = ` ${day}`;
  }
}

function updateLocation() {
  const locationElement = document.getElementById("location");

  if (!locationElement) {
    return;
  }

  if (!navigator.geolocation) {
    locationElement.textContent = " Location unavailable";
    locationElement.title = "Location unavailable";
    return;
  }

  locationElement.textContent = " Fetching location...";

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords;

      try {
        const response = await fetch(
          `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&zoom=10&addressdetails=1`,
          {
            headers: {
              Accept: "application/json",
            },
          },
        );

        if (!response.ok) {
          throw new Error("Reverse geocoding failed");
        }

        const data = await response.json();
        const address = data.address || {};
        const locality =
          address.city ||
          address.town ||
          address.village ||
          address.municipality ||
          address.county ||
          "";
        const province =
          address.state || address.region || address.province || "";
        const country = address.country || "";

        const locationParts = [locality, province, country].filter(Boolean);

        if (locationParts.length > 0) {
          const fullLocation = locationParts.join(", ");
          locationElement.textContent = ` ${fullLocation}`;
          locationElement.title = fullLocation;
        } else if (data.display_name) {
          const displayParts = data.display_name
            .split(",")
            .map((part) => part.trim())
            .filter(Boolean);
          const fallbackLocation = displayParts.slice(0, 3).join(", ");
          locationElement.textContent = ` ${fallbackLocation}`;
          locationElement.title = fallbackLocation;
        } else {
          const coordinateLocation = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
          locationElement.textContent = ` ${coordinateLocation}`;
          locationElement.title = coordinateLocation;
        }
      } catch (error) {
        const coordinateLocation = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
        locationElement.textContent = ` ${coordinateLocation}`;
        locationElement.title = coordinateLocation;
      }
    },
    () => {
      locationElement.textContent = " Location permission denied";
      locationElement.title = "Location permission denied";
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 600000,
    },
  );
}

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("addModal");
  const openButtons = document.querySelectorAll(
    '[data-open-task-modal="true"]',
  );
  const closeButton = document.getElementById("closeModal");

  if (!modal || openButtons.length === 0 || !closeButton) return;

  const openModal = () => {
    modal.style.opacity = "1";
    modal.style.pointerEvents = "auto";
  };

  const closeModal = () => {
    modal.style.opacity = "0";
    modal.style.pointerEvents = "none";
  };

  openButtons.forEach((button) => {
    button.addEventListener("click", openModal);
  });
  closeButton.addEventListener("click", closeModal);
});

document.addEventListener("DOMContentLoaded", () => {
  const totalHoursElement = document.getElementById("todayTotalHours");

  if (!totalHoursElement) {
    return;
  }

  const startTimeRaw = totalHoursElement.dataset.startTime || "";
  const endTimeRaw = totalHoursElement.dataset.endTime || "";
  const initialSecondsRaw = totalHoursElement.dataset.totalSeconds || "0";
  const startTime = startTimeRaw ? new Date(startTimeRaw) : null;
  const endTime = endTimeRaw ? new Date(endTimeRaw) : null;
  let initialSeconds = Number.parseInt(initialSecondsRaw, 10);

  if (!Number.isFinite(initialSeconds) || initialSeconds < 0) {
    initialSeconds = 0;
  }

  const formatDuration = (seconds) => {
    const safeSeconds = Math.max(0, Math.floor(seconds));
    const adjustedSeconds = endTime
      ? Math.max(0, safeSeconds - 3600)
      : safeSeconds;
    const hours = Math.floor(adjustedSeconds / 3600);
    const minutes = Math.floor((adjustedSeconds % 3600) / 60);
    const secs = adjustedSeconds % 60;

    if (endTime) {
      return `${hours}h ${String(minutes).padStart(2, "0")}m`;
    }

    return `${hours}h ${String(minutes).padStart(2, "0")}m ${String(secs).padStart(2, "0")}s`;
  };

  const render = () => {
    if (!startTime || Number.isNaN(startTime.getTime())) {
      totalHoursElement.textContent = "0h 00m";
      return;
    }

    if (endTime && !Number.isNaN(endTime.getTime())) {
      totalHoursElement.textContent = formatDuration(initialSeconds);
      return;
    }

    const nowMs = Date.now();
    const startMs = startTime.getTime();
    const elapsedSeconds = Math.floor(Math.max(0, nowMs - startMs) / 1000);
    totalHoursElement.textContent = formatDuration(elapsedSeconds);
  };

  render();

  if (startTime && !endTime) {
    window.setInterval(render, 1000);
  }
});

setInterval(updateTime, 1000);
updateTime();
updateLocation();
