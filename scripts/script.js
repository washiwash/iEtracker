function updateTime() {
    const date = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const time = date.toLocaleTimeString();
    const day = date.toLocaleDateString(undefined, options);
    document.getElementById('time').innerHTML = ` ${time}`;
    document.getElementById('day').innerHTML = ` ${day}`;
}

function updateLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            const { latitude, longitude } = position.coords;
            document.getElementById('location').innerHTML = 
                ` ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
        });
    }
}

setInterval(updateTime, 1000);
updateTime();
updateLocation();