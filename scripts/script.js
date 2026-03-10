function updateTime() {
  const date = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  const time = date.toLocaleTimeString();
  const day = date.toLocaleDateString(undefined, options);

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

setInterval(updateTime, 1000);
updateTime();
updateLocation();
