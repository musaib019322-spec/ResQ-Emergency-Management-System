document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("authForm");
  const overlay = document.getElementById("scannerOverlay");
  const scanText = document.getElementById("scanText");
  const scanFill = document.getElementById("scanFill");
  const card = document.getElementById("cardContainer");
  const termStatus = document.getElementById("termStatus");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;

    // Reset card animations
    card.classList.remove("denied", "granted");

    // Phase 1: Activate Scanner HUD
    overlay.classList.add("active");
    scanText.innerText = "AUTHENTICATING TELEMETRY HANDSHAKE...";
    scanFill.style.width = "35%";

    try {
      const response = await fetch("authenticate.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ username, password })
      });

      const res = await response.json();

      // Phase 2: Decrypting token animation
      scanFill.style.width = "80%";
      scanText.innerText = "PARSING CREDENTIAL MATRIX...";

      setTimeout(() => {
        scanFill.style.width = "100%";

        if (res.status === "success") {
          // Access Granted Tech Transition
          scanText.style.color = "#69e6b0";
          scanText.innerText = "ACCESS AUTHORIZED // DECRYPTING DASHBOARD";
          card.classList.add("granted");

          setTimeout(() => {
            // Futuristic iris zoom out
            document.body.style.transition = "transform 0.4s ease, opacity 0.4s ease";
            document.body.style.transform = "scale(1.08)";
            document.body.style.opacity = "0";

            setTimeout(() => {
              window.location.href = "index.php";
            }, 350);
          }, 600);
        } else {
          // Access Denied Glitch Transition
          overlay.classList.remove("active");
          scanFill.style.width = "0%";
          card.classList.add("denied");

          termStatus.innerText = "REJECTED: " + res.message.toUpperCase();
          termStatus.style.color = "#ff647f";

          // Sound/vibration indicator
          if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
        }
      }, 500);

    } catch (err) {
      overlay.classList.remove("active");
      scanFill.style.width = "0%";
      card.classList.add("denied");
      termStatus.innerText = "CRITICAL ERROR: GATEWAY OFFLINE";
      termStatus.style.color = "#ff647f";
    }
  });
});