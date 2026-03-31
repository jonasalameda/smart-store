/**
 * Threshold alert handling.
 * Depends on: thresholds, fridgeData, prevTemp, prevHum, thresholdsPrimed
 * being defined in dashboard.js before this file is loaded.
 */

const fridgeKeys = ['Frig1', 'Frig2'];

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