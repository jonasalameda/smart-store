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

// Call backend PHP to send email and increment notification badge
function sendTemperatureAlert(fridgeNumber, currentTemp) {
    fetch(`send-email.php?fridge=${fridgeNumber}&temp=${currentTemp}`)
        .then(res => res.json())
        .then(data => {
            console.log('Email status:', data);

            // Update notification badge
            const notifCount = document.getElementById('notification-count');
            let count = parseInt(notifCount.textContent) || 0;
            notifCount.textContent = count + 1;
        })
        .catch(err => console.error('Email error:', err));
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