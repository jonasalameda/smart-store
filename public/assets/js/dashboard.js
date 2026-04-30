const THERMOMETER_FILL_MAX_PX = 160;
const TEMP_DISPLAY_MIN = -5;
const TEMP_DISPLAY_MAX = 35;

const configuredBaseUrl =
    typeof APP_BASE_URL === "string" ? APP_BASE_URL.trim() : "";
const configuredApiPath =
    typeof APP_API_BASE === "string"
        ? APP_API_BASE.trim().replace(/\/$/, "")
        : "";

/**
 * Use path-only URLs for fetch() so the browser always calls the same origin as the page
 * (e.g. 127.0.0.1 vs localhost would otherwise cause TypeError: Failed to fetch).
 */
function pathFromConfiguredBaseUrl(url) {
    if (!url) {
        return "";
    }
    try {
        if (/^https?:\/\//i.test(url)) {
            return new URL(url).pathname.replace(/\/$/, "");
        }
    } catch (_e) {
        /* fall through */
    }
    return url.replace(/\/$/, "");
}

function inferBaseFromScriptSrc() {
    const scriptTag = document.querySelector('script[src*="dashboard.js"]');
    if (!scriptTag) {
        return "";
    }
    try {
        const srcUrl = new URL(scriptTag.src, window.location.origin);
        let base = srcUrl.pathname.replace(/\/assets\/js\/dashboard\.js$/i, "");
        if (base.endsWith("/public")) {
            base = base.slice(0, -"/public".length);
        }
        return base.replace(/\/$/, "");
    } catch (_err) {
        return "";
    }
}

const apiPathPrefix =
    configuredApiPath ||
    pathFromConfiguredBaseUrl(configuredBaseUrl) ||
    inferBaseFromScriptSrc();

function apiUrl(path) {
    const normalizedPath = path.startsWith("/") ? path : `/${path}`;
    return `${apiPathPrefix}${normalizedPath}`;
}

const initial = typeof phpFridgeData !== "undefined" ? phpFridgeData : null;

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
    Frig1: { temp_threshold: 15, humidity_threshold: 70 },
    Frig2: { temp_threshold: 15, humidity_threshold: 70 },
};

let prevTemp = [null, null];
let prevHum = [null, null];
let thresholdsPrimed = false;

function tempToFillHeightPx(tempC) {
    const t = Math.max(
        TEMP_DISPLAY_MIN,
        Math.min(TEMP_DISPLAY_MAX, Number(tempC)),
    );
    const pct = (t - TEMP_DISPLAY_MIN) / (TEMP_DISPLAY_MAX - TEMP_DISPLAY_MIN);
    return Math.round(pct * THERMOMETER_FILL_MAX_PX);
}

function humidityToIndicatorRotationDeg(humPct) {
    const h = Math.max(0, Math.min(100, Number(humPct)));
    return -90 + (h / 100) * 180;
}

function updateGauges() {
    const tempEls = document.querySelectorAll(".termometer .temperature");
    const humPctEls = document.querySelectorAll(
        ".humidity-gauge .humidity.pct-val",
    );
    const indicators = document.querySelectorAll(".humidity-gauge .indicator");

    fridgeData.forEach((fridge, i) => {
        if (tempEls[i]) {
            const hPx = tempToFillHeightPx(fridge.temp);
            tempEls[i].style.height = hPx + "px";
            tempEls[i].setAttribute("data-value", fridge.temp + "°C");
        }

        if (humPctEls[i]) {
            humPctEls[i].textContent = String(Math.round(fridge.hum));
        }

        if (indicators[i]) {
            indicators[i].style.transform =
                `rotate(${humidityToIndicatorRotationDeg(fridge.hum)}deg)`;
        }
    });
}

function sendTemperatureAlert(fridgeNumber, currentTemp) {
    fetch(
        apiUrl(
            `/send-alert?fridge=${encodeURIComponent(fridgeNumber)}&temp=${encodeURIComponent(currentTemp)}`,
        ),
    )
        .then((res) => res.json())
        .then((data) => {
            console.log("Temperature alert sent:", data);
            pollForReply(fridgeNumber);
            window.alert(
                `${i18nStr("alert_temp", "Temperature alert")} (Fridge ${fridgeNumber})\n\nCurrent temperature: ${currentTemp}°C\nEmail sent!`,
            );
        })
        .catch((err) => console.error("Temperature alert error:", err));
}

function pollForReply(fridgeNumber) {
    const pollInterval = setInterval(() => {
        fetch(
            apiUrl(
                `/api/check-reply?fridge=${encodeURIComponent(fridgeNumber)}`,
            ),
        )
            .then((res) => res.json())
            .then((data) => {
                if (data.reply.includes("yes")) {
                    clearInterval(pollInterval);
                    toggleFan(true);
                    window.alert(
                        `${i18nStr("alert_fan_on", "Turn the fan ON")} for Fridge ${fridgeNumber}!`,
                    );
                } else if (data.reply.includes("no")) {
                    clearInterval(pollInterval);
                    toggleFan(false);
                    window.alert(
                        `${i18nStr("alert_fan_stay_off", "Fan stays OFF")} for Fridge ${fridgeNumber}.`,
                    );
                }
            })
            .catch((err) => console.error("Poll reply error:", err));
    }, 30000);
}

const fanToggle = document.getElementById("fan-toggle");
let fanOn = false;

/** @returns {Record<string, string>} */
function appI18n() {
    return typeof window !== "undefined" &&
        window.__APP_I18N &&
        typeof window.__APP_I18N === "object"
        ? window.__APP_I18N
        : {};
}

function i18nStr(key, fallback) {
    const v = appI18n()[key];
    return v != null && v !== "" ? v : fallback;
}

function applyFanVisualState(on) {
    const fanImg = document.getElementById("fan-img");
    const fanStatus = document.getElementById("fan-status");
    if (!fanToggle || !fanImg || !fanStatus) {
        return;
    }
    if (on) {
        fanToggle.textContent = i18nStr("fan_on", "ON");
        fanToggle.classList.remove("fan-off");
        fanToggle.classList.add("fan-on");
        fanStatus.textContent = i18nStr("fan_status_on", "Status: ON");
        fanImg.style.animation = "fananim 1s linear infinite";
    } else {
        fanToggle.textContent = i18nStr("fan_off", "OFF");
        fanToggle.classList.remove("fan-on");
        fanToggle.classList.add("fan-off");
        fanStatus.textContent = i18nStr("fan_status_off", "Status: OFF");
        fanImg.style.animation = "none";
    }
}

function toggleFan(state = null) {
    const previousOn = fanOn;
    if (state !== null) {
        fanOn = state;
    } else {
        fanOn = !fanOn;
    }

    const fanStatus = document.getElementById("fan-status");
    applyFanVisualState(fanOn);

    const toggleUrl = apiUrl(`/toggle-fan?state=${fanOn ? "on" : "off"}`);
    fetch(toggleUrl)
        .then(async (res) => {
            const text = await res.text();
            if (!res.ok) {
                throw new Error(text || `HTTP ${res.status}`);
            }
            try {
                return JSON.parse(text);
            } catch (_e) {
                throw new Error(
                    "Server did not return JSON (check PHP errors)",
                );
            }
        })
        .then((data) => {
            console.log("Fan toggle response:", data);
            if (data.status !== "success") {
                throw new Error(data.message || "Unexpected response");
            }
            applyFanVisualState(fanOn);
        })
        .catch((err) => {
            console.error(
                "Failed to toggle fan:",
                err,
                "URL:",
                toggleUrl,
                "Page:",
                window.location.href,
            );
            fanOn = previousOn;
            applyFanVisualState(fanOn);
            if (fanStatus) {
                const hint =
                    err &&
                    err.name === "TypeError" &&
                    String(err.message).includes("fetch")
                        ? i18nStr(
                              "fan_status_error_hint",
                              " — use same host in the bar as APP_BASE_URL (e.g. always localhost or always 127.0.0.1)",
                          )
                        : "";
                fanStatus.textContent = `${i18nStr("fan_status_error", "Status: ERROR (network)")}${hint}`;
            }
        });
}

setInterval(() => {
    fetch(apiUrl("/api/fridge-status"))
        .then((res) => res.json())
        .then((data) => {
            if (data.Frig1) {
                fridgeData[0].temp = Number(data.Frig1.temperature) || 0;
                fridgeData[0].hum = Number(data.Frig1.humidity) || 0;
            }
            if (data.Frig2) {
                fridgeData[1].temp = Number(data.Frig2.temperature) || 0;
                fridgeData[1].hum = Number(data.Frig2.humidity) || 0;
            }
            applyThresholdsFromPayload(data);
            updateGauges();
            checkThresholds();
        })
        .catch((err) => console.error("Failed to fetch fridge status:", err));
}, 5000);

function updateNotificationCountBadge(data) {
    const el = document.getElementById("notification-count");
    if (!el) {
        return;
    }
    if (!data || data.success === false) {
        return;
    }
    // Accept { success: true, count } or legacy { count } only (no success field)
    if (data.success !== true && (data.count === undefined || data.count === null)) {
        return;
    }
    const n = Number(data.count ?? 0);
    if (n <= 0) {
        el.textContent = "0";
        el.style.display = "none";
        return;
    }
    el.style.display = "";
    el.textContent = String(n);
}

function fetchNotificationCount() {
    fetch(apiUrl("/api/notification-count"))
        .then((res) => res.json())
        .then(updateNotificationCountBadge)
        .catch(() => {});
}

function fetchNotificationCountLive() {
    fetch(apiUrl("/api/notification-count"), {
        cache: "no-store",
        headers: {
            "Cache-Control": "no-cache",
            Pragma: "no-cache",
        },
    })
        .then((res) => res.json())
        .then(updateNotificationCountBadge)
        .catch(() => {});
}

fetchNotificationCountLive();
setInterval(fetchNotificationCountLive, 1000);

updateGauges();

fetch(apiUrl("/public/assets/other_data/thresholds.json"))
    .then((res) => res.json())
    .then((data) => {
        thresholds = data;
        checkThresholds();
    })
    .catch(() => {
        checkThresholds();
    });

if (fanToggle) {
    fanToggle.addEventListener("click", () => toggleFan());
}

// ─── Frig3 / Pareto Anywhere ────────────────────────────────────────────────

const FRIG3_POLL_MS = 5000; // match the existing fridge poll interval

/** DOM refs — resolved once after page load */
const frig3 = {
    tempEl: document.querySelector(".frig3-temp"),
    humEl: document.querySelector(".frig3-hum"),
    indicatorEl: document.querySelector(".frig3-hum-indicator"),
    luxEl: document.querySelector(".frig3-lux-label"),
    motionIcon: document.querySelector(".frig3-motion-icon"),
    motionLabel: document.querySelector(".frig3-motion-label"),
    batteryEl: document.querySelector(".frig3-battery"),
    tsEl: document.querySelector(".frig3-ts"),
};

function updateFrig3UI(data) {
    if (!data || !data.success) return;

    // Temperature
    if (frig3.tempEl && data.temperature !== null) {
        const hPx = tempToFillHeightPx(data.temperature); // reuse existing helper
        frig3.tempEl.style.height = hPx + "px";
        frig3.tempEl.setAttribute(
            "data-value",
            data.temperature.toFixed(1) + " °C",
        );
    }

    // Humidity
    if (frig3.humEl && data.relativeHumidity !== null) {
        frig3.humEl.textContent = Math.round(data.relativeHumidity);
    }
    if (frig3.indicatorEl && data.relativeHumidity !== null) {
        frig3.indicatorEl.style.transform = `rotate(${humidityToIndicatorRotationDeg(data.relativeHumidity)}deg)`; // reuse helper
    }

    // Luminous flux
    if (frig3.luxEl && data.luminousFlux !== null) {
        frig3.luxEl.textContent = data.luminousFlux + " lx";
    }

    // Motion
    if (frig3.motionLabel && data.isMotionDetected !== null) {
        const detected = Boolean(data.isMotionDetected);
        frig3.motionLabel.textContent = detected ? "Detected" : "Clear";
        if (frig3.motionIcon) {
            frig3.motionIcon.classList.toggle("motion-active", detected);
        }
    }

    // Battery + timestamp
    if (frig3.batteryEl && data.batteryPct !== null) {
        frig3.batteryEl.textContent = data.batteryPct;
    }
    if (frig3.tsEl && data.timestamp) {
        const d = new Date(data.timestamp);
        frig3.tsEl.textContent = "Updated " + d.toLocaleTimeString();
    }
}

function pollFrig3() {
    fetch(apiUrl("/api/pareto-status")) // uses the existing apiUrl() helper
        .then((res) => res.json())
        .then((data) => updateFrig3UI(data))
        .catch((err) => console.error("Frig3 poll error:", err));
}

// Initial fetch + interval
pollFrig3();
setInterval(pollFrig3, FRIG3_POLL_MS);
