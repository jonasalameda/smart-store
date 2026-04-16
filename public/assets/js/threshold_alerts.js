/**
 * Threshold alert handling.
 * Depends on: thresholds, fridgeData, prevTemp, prevHum, thresholdsPrimed (from dashboard.js).
 */

const fridgeKeys = ['Frig1', 'Frig2'];

function appI18nAlert() {
    return typeof window !== 'undefined' && window.__APP_I18N && typeof window.__APP_I18N === 'object'
        ? window.__APP_I18N
        : {};
}

function i18nFmt(key, ...parts) {
    let s = appI18nAlert()[key] || '';
    parts.forEach((p, i) => {
        s = s.split(`{${i}}`).join(String(p));
    });
    return s;
}

let lastAlertTime = [null, null];
const fifteenMinutes = 15 * 60 * 1000;

function checkThresholds() {
    if (!thresholdsPrimed) {
        fridgeData.forEach((fridge, i) => {
            prevTemp[i] = fridge.temp;
            prevHum[i] = fridge.hum;
        });
        thresholdsPrimed = true;
        return;
    }

    const now = Date.now();

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

        const canAlert = lastAlertTime[i] == null || now - lastAlertTime[i] >= fifteenMinutes;

        if (canAlert && (crossedTemp || crossedHum)) {
            lastAlertTime[i] = now;
            if (crossedTemp) {
                sendTemperatureAlert(i + 1, t);
            }
            if (crossedHum) {
                sendHumidityAlert(i + 1, h);
            }
        }

        prevTemp[i] = t;
        prevHum[i] = h;
    });
}

function sendTemperatureAlert(fridgeNumber, currentTemp) {
    fetch(
        apiUrl(
            `/send-alert?fridge=${encodeURIComponent(fridgeNumber)}&temp=${encodeURIComponent(currentTemp)}`
        )
    )
        .then((res) => res.json())
        .then((data) => {
            console.log('Temperature alert sent:', data);
            pollForReply(fridgeNumber);
            window.alert(
                i18nFmt(
                    'alert_temp',
                    String(fridgeNumber),
                    String(currentTemp),
                    data.email_sent ? appI18nAlert().yes || 'yes' : appI18nAlert().no || 'no'
                )
            );
        })
        .catch((err) => console.error('Temperature alert error:', err));
}

function sendHumidityAlert(fridgeNumber, currentHum) {
    fetch(
        apiUrl(
            `/send-alert?fridge=${encodeURIComponent(fridgeNumber)}&humidity=${encodeURIComponent(currentHum)}`
        )
    )
        .then((res) => res.json())
        .then((data) => {
            console.log('Humidity alert sent:', data);
            pollForReply(fridgeNumber);
            window.alert(
                i18nFmt(
                    'alert_hum',
                    String(fridgeNumber),
                    String(Math.round(currentHum)),
                    data.email_sent ? appI18nAlert().yes || 'yes' : appI18nAlert().no || 'no'
                )
            );
        })
        .catch((err) => console.error('Humidity alert error:', err));
}

function pollForReply(fridgeNumber) {
    const pollInterval = setInterval(() => {
        fetch(apiUrl(`/api/check-reply?fridge=${encodeURIComponent(fridgeNumber)}`))
            .then((res) => res.json())
            .then((data) => {
                const reply = (data.reply || '').toLowerCase();
                if (reply.includes('yes')) {
                    clearInterval(pollInterval);
                    toggleFan(true);
                    window.alert(i18nFmt('alert_fan_on', String(fridgeNumber)));
                } else if (reply.includes('no')) {
                    clearInterval(pollInterval);
                    toggleFan(false);
                    window.alert(i18nFmt('alert_fan_stay_off', String(fridgeNumber)));
                }
            })
            .catch((err) => console.error('Poll reply error:', err));
    }, 30000);
}
