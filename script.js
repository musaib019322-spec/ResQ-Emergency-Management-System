const API = "api/latest_data.php";
const ALERTS_API = "api/alerts.php";
const roomNames = {
  1: "Chemistry Lab",
  2: "Computer Lab",
  3: "Classroom",
  4: "Library",
};
const sensorNames = {
  1: "Flame detection / Digital",
  2: "Gas & smoke / Analog",
  3: "Temperature & humidity",
  4: "Temperature & humidity",
};
let soundEnabled = false,
  audioContext = null,
  previousAlerts = new Set(),
  firstPoll = true;
const $ = (id) => document.getElementById(id);

function escapeHTML(value) {
  return String(value ?? "").replace(
    /[&<>"']/g,
    (char) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        char
      ],
  );
}
function formatNumber(value, suffix = "") {
  if (
    value === null ||
    value === undefined ||
    value === "" ||
    !Number.isFinite(Number(value))
  )
    return "—";
  return Number(value).toFixed(1) + suffix;
}
function formatTime(date = new Date()) {
  return date.toLocaleTimeString([], {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}
function updateClock() {
  $("clock").textContent = formatTime();
}
updateClock();
setInterval(updateClock, 15000);

$("soundBtn").addEventListener("click", async () => {
  try {
    const Audio = window.AudioContext || window.webkitAudioContext;
    if (!Audio) throw new Error("Audio unavailable");
    audioContext = audioContext || new Audio();
    await audioContext.resume();
    soundEnabled = true;
    $("soundBtn").innerHTML = "♫ <span>Sound enabled</span>";
    $("soundBtn").style.borderColor = "var(--green)";
    playAlarm();
  } catch (error) {
    $("soundBtn").textContent = "Audio unavailable";
  }
});
function playAlarm() {
  if (!soundEnabled || !audioContext) return;
  [0, 0.22, 0.44].forEach((delay) => {
    const oscillator = audioContext.createOscillator(),
      gain = audioContext.createGain(),
      start = audioContext.currentTime + delay;
    oscillator.type = "sine";
    oscillator.frequency.value = 880;
    gain.gain.setValueAtTime(0.001, start);
    gain.gain.exponentialRampToValueAtTime(0.13, start + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.001, start + 0.14);
    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    oscillator.start(start);
    oscillator.stop(start + 0.15);
  });
}
function getRoomState(room) {
  const online = Number(room.is_online) === 1,
    valid = Number(room.valid_reading) === 1,
    alert = Number(room.is_alert) === 1;
  if (!online) return "offline";
  if (!valid) return "error";
  if (alert) return "alert";
  return "online";
}
function makeRoom(room) {
  const id = Number(room.room_id),
    state = getRoomState(room);
  const labels = {
    online: ["NORMAL", "normal"],
    alert: ["ACTIVE ALERT", "alert"],
    error: ["SENSOR ERROR", "error"],
    offline: ["NO RECENT DATA", "offline"],
  };
  let telemetry = "";
  if (id === 1) {
    const flame = room.flame_detected;
    telemetry = `<div><strong>${flame == null ? "—" : Number(flame) === 1 ? "DETECTED" : "CLEAR"}</strong><small>Flame detection</small></div><div><strong>${flame == null ? "—" : Number(flame) === 1 ? "ALERT" : "NORMAL"}</strong><small>Digital sensor state</small></div>`;
  } else if (id === 2) {
    const gas = room.gas_value;
    const level =
      gas == null
        ? "—"
        : Number(gas) >= 1800
          ? "HIGH"
          : Number(gas) >= 1000
            ? "ELEVATED"
            : "NORMAL";
    telemetry = `<div><strong>${gas == null ? "—" : escapeHTML(gas)}</strong><small>MQ-2 analog reading</small></div><div><strong>${level}</strong><small>Configured indication</small></div>`;
  } else {
    telemetry = `<div><strong>${formatNumber(room.temperature, "°C")}</strong><small>Temperature</small></div><div><strong>${formatNumber(room.humidity, "%")}</strong><small>Relative humidity</small></div>`;
  }
  const updated = room.updated_at
    ? escapeHTML(room.updated_at)
    : "No readings recorded";
  return `<article class="room-card ${state}">
    <div class="room-head"><div><div class="room-code">SENSOR CHANNEL / ${String(id).padStart(2, "0")}</div><h3 class="room-title">${escapeHTML(room.room_name || roomNames[id] || "Unknown room")}</h3><div class="room-sensor">${sensorNames[id] || "Environmental sensor"}</div></div><span class="room-status ${labels[state][1]}">${labels[state][0]}</span></div>
    <div class="telemetry">${telemetry}</div><div class="room-footer"><span>LAST READING · ${updated}</span><span class="signal" aria-label="Channel status"><i></i><i></i><i></i></span></div>
  </article>`;
}
function updateMap(room) {
  const id = Number(room.room_id),
    state = getRoomState(room),
    node = document.querySelector(`[data-map-room="${id}"]`),
    label = $("mapState" + id);
  if (!node || !label) return;
  node.dataset.state = state;
  label.textContent = {
    online: "● NORMAL",
    alert: "● ACTIVE ALERT",
    error: "● SENSOR ERROR",
    offline: "○ NO DATA",
  }[state];
}
async function refreshRooms() {
  try {
    const response = await fetch(API + "?t=" + Date.now(), {
      cache: "no-store",
    });
    if (!response.ok) throw new Error("API returned HTTP " + response.status);
    const data = await response.json();
    if (!data.success) throw new Error(data.message || "API failure");
    const rooms = data.rooms || [];
    const completeRooms = [1, 2, 3, 4].map(
      (id) =>
        rooms.find((room) => Number(room.room_id) === id) || {
          room_id: id,
          room_name: roomNames[id],
          valid_reading: 0,
          is_online: 0,
          is_alert: 0,
        },
    );
    $("roomsGrid").innerHTML = completeRooms.map(makeRoom).join("");
    completeRooms.forEach(updateMap);
    const currentAlerts = new Set(
      completeRooms
        .filter((room) => getRoomState(room) === "alert")
        .map((room) => Number(room.room_id)),
    );
    if (!firstPoll && [...currentAlerts].some((id) => !previousAlerts.has(id)))
      playAlarm();
    previousAlerts = currentAlerts;
    firstPoll = false;
    $("alertCount").textContent = String(currentAlerts.size).padStart(2, "0");
    $("alertSummary").textContent = currentAlerts.size
      ? "Immediate attention required"
      : "No active sensor alerts";
    const deviceOnline = Boolean(data.device_online);
    $("deviceState").textContent = deviceOnline ? "CONNECTED" : "OFFLINE";
    $("sideDevice").textContent = deviceOnline ? "CONNECTED" : "OFFLINE";
    $("deviceState").style.color = deviceOnline ? "var(--green)" : "var(--red)";
    $("connection").textContent = deviceOnline
      ? "ONLINE / RECEIVING DATA"
      : "WAITING FOR DATA";
    $("connection").style.color = deviceOnline
      ? "var(--green)"
      : "var(--amber)";
    const orb = document.querySelector(".status-ring");
    if (orb)
      orb.style.borderColor = deviceOnline ? "var(--green)" : "var(--amber)";
    $("lastSync").textContent = formatTime();
  } catch (error) {
    $("connection").textContent = "API CONNECTION ERROR";
    $("connection").style.color = "var(--red)";
    $("deviceState").textContent = "API OFFLINE";
    $("deviceState").style.color = "var(--red)";
    $("sideDevice").textContent = "API OFFLINE";
    $("lastSync").textContent = "—";
    // Do not replace the whole dashboard with fake readings: show the connection failure honestly.
  }
}
async function refreshAlerts() {
  try {
    const response = await fetch(ALERTS_API + "?t=" + Date.now(), {
      cache: "no-store",
    });
    if (!response.ok) throw new Error("Alerts API unavailable");
    const data = await response.json(),
      alerts = data.alerts || [];
    if (!alerts.length) {
      $("alerts").innerHTML =
        '<div class="empty-state"><span class="empty-mark">⌁</span><b>No recorded incidents</b><small>New backend alerts will appear here when available.</small></div>';
      return;
    }
    $("alerts").innerHTML = alerts
      .slice(0, 8)
      .map(
        (alert) =>
          `<div class="event-row"><div class="event-symbol">!</div><div><strong>${escapeHTML(alert.title || alert.message || "Sensor alert")}</strong><small>${escapeHTML(alert.room_name || roomNames[alert.room_id] || "Room")} <span class="event-severity"> / ${escapeHTML(alert.severity || "ALERT")}</span></small><small>${escapeHTML(alert.created_at || "")}</small></div></div>`,
      )
      .join("");
  } catch (error) {
    $("alerts").innerHTML =
      '<div class="empty-state"><span class="empty-mark">⌁</span><b>Event history unavailable</b><small>Check the PHP alerts API connection.</small></div>';
  }
}
async function refreshDashboard() {
  await Promise.all([refreshRooms(), refreshAlerts()]);
}
refreshDashboard();
setInterval(refreshDashboard, 3000);

// Highlight the section currently in view and close the mobile navigation naturally after a tap.
const navLinks = [...document.querySelectorAll(".nav-item, .mobile-nav a")];
const sections = ["overview", "campus", "rooms", "events"]
  .map((id) => document.getElementById(id))
  .filter(Boolean);
if ("IntersectionObserver" in window) {
  const observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((e) => e.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (!visible) return;
      navLinks.forEach((link) =>
        link.classList.toggle(
          "active",
          link.getAttribute("href") === "#" + visible.target.id,
        ),
      );
    },
    { rootMargin: "-18% 0px -65% 0px", threshold: [0, 0.15, 0.35, 0.6] },
  );
  sections.forEach((section) => observer.observe(section));
}
