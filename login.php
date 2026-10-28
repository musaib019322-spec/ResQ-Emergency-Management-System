<?php
session_start();
if (isset($_SESSION['resq_user'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#080b12">
  <title>ResQ — Clearance Protocol</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <!-- Ambient cyber halos -->
  <div class="ambient-glow"></div>
  <div class="hud-grid"></div>

  <div class="login-wrapper">
    <!-- Outer Cyber Chamfer Frame -->
    <div class="cyber-card" id="cardContainer">
      <div class="card-edge top-left"></div>
      <div class="card-edge top-right"></div>
      <div class="card-edge bottom-left"></div>
      <div class="card-edge bottom-right"></div>

      <!-- Header Section -->
      <header class="card-header">
        <div class="logo-box">
          <svg viewBox="0 0 160 160" width="38" height="38" fill="none">
            <rect width="160" height="160" rx="30" fill="#0d1924" stroke="#2a4756" stroke-width="3"/>
            <path d="M80 34 L114 51 V84 C114 105 80 124 80 124 C80 124 46 105 46 84 V51 Z" stroke="#3ba9b2" stroke-width="4" fill="#102530"/>
            <path d="M48 80 H62 L69 66 L75 95 L84 57 L91 85 L97 80 H112" stroke="#63e8ed" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="84" cy="57" r="5" fill="#69e6b0"/>
          </svg>
        </div>
        <div class="title-meta">
          <div class="kicker"><span class="blink-dot"></span> SECURITY GATEWAY</div>
          <h1>ResQ<span>.</span> AUTH</h1>
        </div>
      </header>

      <!-- Terminal Diagnostics Row -->
      <div class="terminal-bar">
        <span id="termStatus">READY FOR TELEMETRY HANDSHAKE</span>
        <span class="mono-hash">NODE // ESP32-01</span>
      </div>

      <!-- Authentication Form -->
      <form id="authForm" autocomplete="off">
        <div class="field-group">
          <label for="username">OPERATOR ID</label>
          <div class="input-wrap">
            <span class="input-icon">⌘</span>
            <input type="text" id="username" name="username" placeholder="Enter operator ID" required>
            <div class="focus-bracket"></div>
          </div>
        </div>

        <div class="field-group">
          <label for="password">SECURITY PASSPHRASE</label>
          <div class="input-wrap">
            <span class="input-icon">⚿</span>
            <input type="password" id="password" name="password" placeholder="••••••••••••" required>
            <div class="focus-bracket"></div>
          </div>
        </div>

        <button type="submit" id="submitBtn" class="cyber-btn">
          <span class="btn-scanline"></span>
          <span class="btn-text">INITIALIZE PROTOCOL</span>
          <span class="btn-icon">→</span>
        </button>
      </form>

      <!-- Holographic Scanning Overlay (Triggered on Submit) -->
      <div class="scanner-overlay" id="scannerOverlay">
        <div class="radar-circle"></div>
        <div class="radar-line"></div>
        <div class="scan-status" id="scanText">VERIFYING OPERATOR SIGNATURE...</div>
        <div class="scan-progress-bar"><div class="fill" id="scanFill"></div></div>
      </div>

      <footer class="card-footer">
        <span>SECURITY ENCRYPTION: <b>AES-256 / SHA-256</b></span>
        <span>LEVEL: <b>01-CLEARANCE</b></span>
      </footer>
    </div>
  </div>

  <script src="js/login.js"></script>
</body>
</html>