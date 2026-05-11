/**
 * Threshold alert handling.
 * Depends on: thresholds, fridgeData, prevTemp, prevHum, thresholdsPrimed
 * being defined in dashboard.js before this file is loaded.
 */

const fridgeKeys = ["Frig1", "Frig2"];
let alertsSentThisSession = {}; // Track which alerts we've sent on this page load

function checkThresholds() {
    // On first call: check immediately and prime, don't return early
    // This ensures alerts are sent on initial page load
    if (!thresholdsPrimed) {
        thresholdsPrimed = true;
        // Fall through to check thresholds on first load
    }

    fridgeData.forEach((fridge, i) => {
        const t = fridge.temp;
        const h = fridge.hum;
        const fridgeNumber = i + 1;
        const key = fridgeKeys[i];
        const tempLimit = thresholds[key]?.temp_threshold ?? 25;
        const humLimit = thresholds[key]?.humidity_threshold ?? 70;

        // Check if current value exceeds threshold
        const tempExceeds = t >= tempLimit;
        const humExceeds = h >= humLimit;

        // Send alert if threshold exceeded and we haven't already sent one this session
        if (tempExceeds && !alertsSentThisSession[`temp-${fridgeNumber}`]) {
            sendTemperatureAlert(fridgeNumber, t);
            alertsSentThisSession[`temp-${fridgeNumber}`] = true;
        }

        if (humExceeds && !alertsSentThisSession[`hum-${fridgeNumber}`]) {
            sendHumidityAlert(fridgeNumber, h);
            alertsSentThisSession[`hum-${fridgeNumber}`] = true;
        }

        prevTemp[i] = t;
        prevHum[i] = h;
    });
}
