// Simulated temperature and humidity readings
let fridgeData = [
    { temp: 20, hum: 50, threshold: 25 }, // Fridge 1
    { temp: 18, hum: 45, threshold: 22 }  // Fridge 2
];

// Update gauges on the page
function updateGauges() {
    const tempEls = document.querySelectorAll('.temperature');
    const humEls = document.querySelectorAll('.humidity');

    fridgeData.forEach((fridge, i) => {
        
        tempEls[i].style.height = fridge.temp * 4 + 'px';
        tempEls[i].setAttribute('data-value', fridge.temp + '°C');

        
        humEls[i].textContent = fridge.hum;
    });
}

// Check temperature thresholds and call backend email
function checkThresholds() {
    fridgeData.forEach((fridge, i) => {
        if (fridge.temp > fridge.threshold) {
            sendTemperatureAlert(i + 1, fridge.temp);
        }
    });
}

// Call backend PHP to send fridge alert email, then show a system notification (alert + temperature)
function sendTemperatureAlert(fridgeNumber, currentTemp) {
    fetch(`send-email.php?fridge=${encodeURIComponent(fridgeNumber)}&temp=${encodeURIComponent(currentTemp)}`)
        .then((res) => res.json())
        .then((data) => {
            console.log('Email status:', data);

            if (data.status !== 'success' || !data.system_notification) {
                return;
            }

            const { title, body } = data.system_notification;

            if (!('Notification' in window)) {
                window.alert(`${title}\n\n${body}`);
                return;
            }

            const show = () => {
                try {
                    new Notification(title, { body, silent: false });
                } catch (e) {
                    window.alert(`${title}\n\n${body}`);
                }
            };

            if (Notification.permission === 'granted') {
                show();
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then((perm) => {
                    if (perm === 'granted') {
                        show();
                    } else {
                        window.alert(`${title}\n\n${body}`);
                    }
                });
            } else {
                window.alert(`${title}\n\n${body}`);
            }
        })
        .catch((err) => console.error('Email error:', err));
}

// Fan toggle logic 
const fanToggle = document.getElementById('fan-toggle');
let fanOn = false;

function toggleFan(state = null) {
    if (state !== null) fanOn = state;
    else fanOn = !fanOn;

    const fanImg = document.getElementById('fan-img');
    const fanStatus = document.getElementById('fan-status');

    if (fanOn) {
        fanToggle.textContent = 'ON';
        fanToggle.classList.remove('fan-off');
        fanToggle.classList.add('fan-on');
        fanStatus.textContent = 'Status: ON';
        fanImg.style.animation = 'fananim 1s linear infinite';
    } else {
        fanToggle.textContent = 'OFF';
        fanToggle.classList.remove('fan-on');
        fanToggle.classList.add('fan-off');
        fanStatus.textContent = 'Status: OFF';
        fanImg.style.animation = 'none';
    }
}

fanToggle.addEventListener('click', () => toggleFan());

// Simulate updating readings every 5 seconds
setInterval(() => {
    fridgeData.forEach(f => f.temp = Math.floor(Math.random() * 10 + 18));
    fridgeData.forEach(f => f.hum = Math.floor(Math.random() * 20 + 40));

    updateGauges();
    checkThresholds();
}, 5000);

// Initial update
updateGauges();