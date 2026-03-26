/**
 * Fridge dashboard: thermometer fill + humidity arc follow numeric values.
 * Alerts (email + system notification) when crossing thresholds:
 *   temperature >= 18°C, humidity >= 50%
 */
const TEMP_ALERT_C = 18;
const HUM_ALERT_PCT = 50;

// Thermometer column inner fill max height (px) — matches .termometer height minus padding/bulb
const THERMOMETER_FILL_MAX_PX = 160;
const TEMP_DISPLAY_MIN = -5;
const TEMP_DISPLAY_MAX = 35;

// Seed from PHP when present (DashboardController), else defaults
const initial = typeof phpFridgeData !== 'undefined' ? phpFridgeData : null;

let fridgeData = [
    {
        temp: initial ? Number(initial.Frig1.temperature) || 18 : 18,
        hum: initial ? Number(initial.Frig1.humidity) || 45 : 45,
    },
    {
        temp: initial ? Number(initial.Frig2.temperature) || 18 : 18,
        hum: initial ? Number(initial.Frig2.humidity) || 45 : 45,
    },
];

/** Last readings — used to detect crossing threshold (avoid spamming every interval) */
let prevTemp = [null, null];
let prevHum = [null, null];
let thresholdsPrimed = false;

function tempToFillHeightPx(tempC) {
    const t = Math.max(TEMP_DISPLAY_MIN, Math.min(TEMP_DISPLAY_MAX, Number(tempC)));
    const pct = (t - TEMP_DISPLAY_MIN) / (TEMP_DISPLAY_MAX - TEMP_DISPLAY_MIN);
    return Math.round(pct * THERMOMETER_FILL_MAX_PX);
}

/** Humidity arc: indicator sweeps from -90° (0%) to +90° (100%) */
function humidityToIndicatorRotationDeg(humPct) {
    const h = Math.max(0, Math.min(100, Number(humPct)));
    return -90 + (h / 100) * 180;
}

function updateGauges() {
    const tempEls = document.querySelectorAll('.termometer .temperature');
    const humPctEls = document.querySelectorAll('.humidity-gauge .humidity.pct-val');
    const indicators = document.querySelectorAll('.humidity-gauge .indicator');

    fridgeData.forEach((fridge, i) => {
        if (tempEls[i]) {
            const hPx = tempToFillHeightPx(fridge.temp);
            tempEls[i].style.height = hPx + 'px';
            tempEls[i].setAttribute('data-value', fridge.temp + '°C');
        }

        if (humPctEls[i]) {
            humPctEls[i].textContent = String(Math.round(fridge.hum));
        }

        if (indicators[i]) {
            indicators[i].style.transform = `rotate(${humidityToIndicatorRotationDeg(fridge.hum)}deg)`;
        }
    });
}

function checkThresholds() {
    if (!thresholdsPrimed) {
        fridgeData.forEach((fridge, i) => {
            prevTemp[i] = fridge.temp;
            prevHum[i] = fridge.hum;
        });
        thresholdsPrimed = true;
        return;
    }

    fridgeData.forEach((fridge, i) => {
        const t = fridge.temp;
        const h = fridge.hum;

        const crossedTemp =
            t >= TEMP_ALERT_C &&
            (prevTemp[i] === null || prevTemp[i] < TEMP_ALERT_C);

        const crossedHum =
            h >= HUM_ALERT_PCT &&
            (prevHum[i] === null || prevHum[i] < HUM_ALERT_PCT);

        if (crossedTemp) {
            sendTemperatureAlert(i + 1, t);
        }
        if (crossedHum) {
            sendHumidityAlert(i + 1, h);
        }

        prevTemp[i] = t;
        prevHum[i] = h;
    });
}

function showSystemNotificationFromPayload(data) {
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
}

function sendTemperatureAlert(fridgeNumber, currentTemp) {
    fetch(
        `send-alert?fridge=${encodeURIComponent(fridgeNumber)}&temp=${encodeURIComponent(currentTemp)}`
    )
        .then((res) => res.json())
        .then((data) => {
            console.log('Temperature alert email:', data);
            showSystemNotificationFromPayload(data);
        })
        .catch((err) => console.error('Temperature alert email error:', err));
}

function sendHumidityAlert(fridgeNumber, currentHum) {
    fetch(
        `send-alert?fridge=${encodeURIComponent(fridgeNumber)}&humidity=${encodeURIComponent(currentHum)}`
    )
        .then((res) => res.json())
        .then((data) => {
            console.log('Humidity alert email:', data);
            showSystemNotificationFromPayload(data);
        })
        .catch((err) => console.error('Humidity alert email error:', err));
}

const fanToggle = document.getElementById('fan-toggle');
let fanOn = false;

function toggleFan(state = null) {
    if (state !== null) {
        fanOn = state;
    } else {
        fanOn = !fanOn;
    }

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

if (fanToggle) {
    fanToggle.addEventListener('click', () => toggleFan());
}

setInterval(() => {
    fridgeData.forEach((f) => {
        // Independent readings per fridge; ranges allow crossing thresholds
        f.temp = Math.floor(Math.random() * 16) + 5;
        f.hum = Math.floor(Math.random() * 61) + 20;
    });

    updateGauges();
    checkThresholds();
}, 5000);

updateGauges();
checkThresholds();
