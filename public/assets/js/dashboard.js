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

let thresholds = {
    Frig1: { temp_threshold: 25, humidity_threshold: 70 },
    Frig2: { temp_threshold: 25, humidity_threshold: 70 }
};

let prevTemp = [null, null];
let prevHum = [null, null];
let thresholdsPrimed = false;
//don't wanna spam notifs

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

    const fridgeKeys = ['Frig1', 'Frig2'];

    fridgeData.forEach((fridge, i) => {
        const t = fridge.temp;
        const h = fridge.hum;
        const key = fridgeKeys[i];
        const tempLimit = thresholds[key]?.temp_threshold ?? 25;
        const humLimit = thresholds[key]?.humidity_threshold ?? 70;

        const crossedTemp =
            t >= tempLimit &&
            (prevTemp[i] === null || prevTemp[i] < tempLimit);

        const crossedHum =
            h >= humLimit &&
            (prevHum[i] === null || prevHum[i] < humLimit);

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

function sendTemperatureAlert(fridgeNumber, currentTemp) {
    window.alert(
        `Temperature alert (Fridge ${fridgeNumber})\n\nCurrent temperature: ${currentTemp}°C`
    );
}

function sendHumidityAlert(fridgeNumber, currentHum) {
    window.alert(
        `Humidity alert (Fridge ${fridgeNumber})\n\nCurrent humidity: ${Math.round(currentHum)}%`
    );
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
    fetch('/smart-store/api/fridge-status')
        .then(res => res.json())
        .then(data => {
            if (data.Frig1) {
                fridgeData[0].temp = Number(data.Frig1.temperature) || 0;
                fridgeData[0].hum = Number(data.Frig1.humidity) || 0;
            }
            if (data.Frig2) {
                fridgeData[1].temp = Number(data.Frig2.temperature) || 0;
                fridgeData[1].hum = Number(data.Frig2.humidity) || 0;
            }
            updateGauges();
            checkThresholds();
        })
        .catch(err => console.error('Failed to fetch fridge status:', err));
}, 5000);

updateGauges();

// Load thresholds then run first check
fetch('/assets/other_data/thresholds.json')
    .then(res => res.json())
    .then(data => {
        thresholds = data;
        console.log('Thresholds loaded:', thresholds);
        checkThresholds();
    })
    .catch(err => {
        console.error('Failed to load thresholds:', err);
        checkThresholds();
    });