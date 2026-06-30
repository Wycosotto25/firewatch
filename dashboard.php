<?php
// ============================================================
//  dashboard.php  –  Main FIREWATCH control dashboard
// ============================================================
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db       = getDB();
$userId   = (int) $_SESSION['user_id'];
$fullname = htmlspecialchars($_SESSION['fullname']);

// ── Latest sensor reading ────────────────────────────────────
$sensorRow = $db->query(
    'SELECT * FROM sensor_data ORDER BY recorded_at DESC LIMIT 1'
)->fetch_assoc() ?? [
    'temperature' => '--', 'humidity' => '--',
    'gas_level' => '--', 'flame_detected' => 0
];

// ── Actuator state ───────────────────────────────────────────
$actuator = $db->query(
    'SELECT * FROM actuator_state ORDER BY id DESC LIMIT 1'
)->fetch_assoc() ?? ['pump' => 0, 'buzzer' => 0, 'fan' => 0, 'emergency' => 0];

// ── Incident log (last 20) ───────────────────────────────────
$logs = $db->query(
    'SELECT i.*, u.fullname
     FROM incidents i
     JOIN users u ON u.id = i.user_id
     ORDER BY i.created_at DESC
     LIMIT 20'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — F.I.R.E.W.A.T.C.H</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
<script>
  // Instant theme initial check (prevents white flash on load)
  const savedTheme = localStorage.getItem('firewatch-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
</script>

<header class="topbar">
  <div class="topbar-left">
    <span class="topbar-fire">🔥</span>
    <span class="topbar-title">F.I.R.E.W.A.T.C.H</span>
  </div>
  <div class="topbar-right">
    <button id="arduinoConnectBtn" class="btn btn-primary btn-sm" style="box-shadow: none;">🔌 Connect Arduino</button>
    
    <button id="themeToggle" class="theme-toggle-btn" title="Toggle Theme">
      <span id="themeToggleIcon">🌗</span>
    </button>
    
    <span class="topbar-user">👤 <?= $fullname ?></span>
    <a href="team.php" class="btn btn-ghost btn-sm">👥 Meet the Team</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
  </div>
</header>

<div class="status-bar" id="statusBar">
  <span class="status-dot" id="statusDot"></span>
  <span id="statusText">Connecting to sensor network…</span>
  <span class="status-time" id="statusTime"></span>
</div>

<main class="dashboard-main">

  <section class="welcome-banner">
    <div>
      <h2>Welcome back, <?= $fullname ?></h2>
      <p>Real-time fire detection &amp; emergency control system is active.</p>
    </div>
    <div class="welcome-badge" id="alertBadge">
      <span id="alertBadgeText">MONITORING</span>
    </div>
  </section>

  <section class="section-title">Live Sensor Readings</section>
  <div class="sensor-grid">

    <div class="sensor-card" id="card-temp">
      <div class="sensor-icon">🌡️</div>
      <div class="sensor-label">Temperature</div>
      <div class="sensor-value" id="val-temp">
        <?= $sensorRow['temperature'] ?>
      </div>
      <div class="sensor-unit">°C</div>
      <div class="sensor-status" id="st-temp">—</div>
    </div>

    <div class="sensor-card" id="card-hum">
      <div class="sensor-icon">💧</div>
      <div class="sensor-label">Humidity</div>
      <div class="sensor-value" id="val-hum">
        <?= $sensorRow['humidity'] ?>
      </div>
      <div class="sensor-unit">%</div>
      <div class="sensor-status" id="st-hum">—</div>
    </div>

    <div class="sensor-card" id="card-gas">
      <div class="sensor-icon">💨</div>
      <div class="sensor-label">Gas / Smoke</div>
      <div class="sensor-value" id="val-gas">
        <?= $sensorRow['gas_level'] ?>
      </div>
      <div class="sensor-unit">ppm</div>
      <div class="sensor-status" id="st-gas">—</div>
    </div>

    <div class="sensor-card" id="card-flame">
      <div class="sensor-icon">🔥</div>
      <div class="sensor-label">Flame Sensor</div>
      <div class="sensor-value" id="val-flame">
        <?= $sensorRow['flame_detected'] ? 'DETECTED' : 'CLEAR' ?>
      </div>
      <div class="sensor-unit"></div>
      <div class="sensor-status" id="st-flame">—</div>
    </div>

  </div>

  <div class="control-row">

    <section class="control-hub">
      <div class="section-title">⚙️ Control Hub</div>
      <p class="control-note">Toggle actuators manually or let the Arduino decide automatically.</p>

      <div class="actuator-list">

        <div class="actuator-item">
          <div class="actuator-info">
            <span class="actuator-icon">💧</span>
            <div>
              <div class="actuator-name">Water Pump</div>
              <div class="actuator-desc">Suppression system</div>
            </div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" id="tog-pump"
              <?= $actuator['pump'] ? 'checked' : '' ?>
              onchange="setActuator('pump', this.checked)">
            <span class="toggle-slider"></span>
          </label>
        </div>

        <div class="actuator-item">
          <div class="actuator-info">
            <span class="actuator-icon">🔔</span>
            <div>
              <div class="actuator-name">Buzzer</div>
              <div class="actuator-desc">Audible alarm</div>
            </div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" id="tog-buzzer"
              <?= $actuator['buzzer'] ? 'checked' : '' ?>
              onchange="setActuator('buzzer', this.checked)">
            <span class="toggle-slider"></span>
          </label>
        </div>

        <div class="actuator-item">
          <div class="actuator-info">
            <span class="actuator-icon">🌀</span>
            <div>
              <div class="actuator-name">Ventilation Fan</div>
              <div class="actuator-desc">Smoke dispersal</div>
            </div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" id="tog-fan"
              <?= $actuator['fan'] ? 'checked' : '' ?>
              onchange="setActuator('fan', this.checked)">
            <span class="toggle-slider"></span>
          </label>
        </div>

      </div>
    </section>

    <section class="emergency-panel">
      <div class="section-title">🚨 Emergency</div>
      <p class="control-note">Instantly activates <strong>all</strong> actuators and logs an incident.</p>
      <button class="btn-emergency" id="emergencyBtn" onclick="triggerEmergency()">
        <span class="emergency-icon">🚨</span>
         EMERGENCY MODE
      </button>
      <div class="emergency-note" id="emergencyNote">Clicking will instantly engage all fire mitigation systems</div>
    </section>

  </div>

<section class="log-section">
    <div class="log-header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
      <div class="section-title" style="margin-bottom: 0;">📋 Incident Log</div>
      <button class="btn btn-primary btn-sm" onclick="openHistoryModal()">🔍 View All History Logs</button>
    </div>
    <div class="table-wrap">
      <table class="log-table" id="incidentTable">
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Type</th>
            <th>Temp (°C)</th>
            <th>Humidity (%)</th>
            <th>Gas (ppm)</th>
            <th>Flame</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody id="logBody">
          <?php while ($row = $logs->fetch_assoc()): 
              $displayType = !empty($row['incident_type']) ? $row['incident_type'] : ((int)$row['flame_detected'] === 1 ? 'fire' : '');
          ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['fullname']) ?></td>
            <td><span class="badge badge-<?= $displayType ?>"><?= strtoupper($displayType) ?></span></td>
            <td><?= $row['temperature'] ?></td>
            <td><?= $row['humidity'] ?></td>
            <td><?= $row['gas_level'] ?></td>
            <td><?= $row['flame_detected'] ? '🔥 YES' : '✅ NO' ?></td>
            <td><?= $row['created_at'] ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php if (!$logs->num_rows): ?>
        <div class="empty-log" id="emptyLogField">No incidents recorded yet.</div>
      <?php endif; ?>
    </div>
  </section>

</main>

<div class="modal-overlay" id="historyLogModal" style="display: none;">
  <div class="modal-box" style="width: 95%; max-width: 1200px; margin: auto; padding: 25px; text-align: left; transform: scale(1);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
      <h3 style="margin: 0; font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; color: var(--ember-bright);">📋 COMPREHENSIVE INCIDENT HISTORY ENGINE</h3>
      <button class="btn btn-ghost btn-sm" onclick="closeHistoryModal()" style="font-size: 1.2rem; font-weight: 700; padding: 4px 12px;">✕</button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; background: var(--bg-surface); padding: 15px; border-radius: var(--radius); border: 1px solid var(--border);">
      <div>
        <label style="display: block; font-size: 0.8rem; margin-bottom: 4px; color: var(--text-secondary);">Incident Classification:</label>
        <select id="histFilterType" onchange="fetchHistoryLogs(1)" style="width: 100%; padding: 8px; background: var(--bg-deep); border: 1px solid var(--border); color: var(--text-primary); border-radius: 4px;">
          <option value="">All Logs</option>
          <option value="fire">FIRE</option>
          <option value="gas">GAS</option>
          <option value="temp">TEMP</option>
          <option value="manual">MANUAL</option>
          <option value="emergency">EMERGENCY</option>
          <option value="clear">CLEAR</option>
        </select>
      </div>
      <div>
        <label style="display: block; font-size: 0.8rem; margin-bottom: 4px; color: var(--text-secondary);">Search Operator User:</label>
        <input type="text" id="histSearchUser" oninput="fetchHistoryLogs(1)" placeholder="Type name..." style="width: 100%; padding: 8px; background: var(--bg-deep); border: 1px solid var(--border); color: var(--text-primary); border-radius: 4px; box-sizing: border-box;">
      </div>
      <div>
        <label style="display: block; font-size: 0.8rem; margin-bottom: 4px; color: var(--text-secondary);">Date Starting From:</label>
        <input type="date" id="histDateFrom" onchange="fetchHistoryLogs(1)" style="width: 100%; padding: 8px; background: var(--bg-deep); border: 1px solid var(--border); color: var(--text-primary); border-radius: 4px;">
      </div>
      <div>
        <label style="display: block; font-size: 0.8rem; margin-bottom: 4px; color: var(--text-secondary);">Date Running To:</label>
        <input type="date" id="histDateTo" onchange="fetchHistoryLogs(1)" style="width: 100%; padding: 8px; background: var(--bg-deep); border: 1px solid var(--border); color: var(--text-primary); border-radius: 4px;">
      </div>
    </div>

    <div class="table-wrap" style="max-height: 450px; overflow-y: auto; border: 1px solid var(--border);">
      <table class="log-table" style="width: 100%;">
        <thead style="position: sticky; top: 0; background: var(--bg-surface); z-index: 2;">
          <tr>
            <th>#</th>
            <th>Authorized Operator</th>
            <th>Classification</th>
            <th>Core Temp</th>
            <th>Relative Hum</th>
            <th>Gas Vol</th>
            <th>Flame Matrix</th>
            <th>Log Timestamp</th>
          </tr>
        </thead>
        <tbody id="historyLogTableBody">
          </tbody>
      </table>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border);">
      <div id="historyPaginationInfo" style="font-size: 0.9rem; color: var(--text-secondary);">Showing page 1 of 1</div>
      <div style="display: flex; gap: 8px;">
        <button id="btnHistPrev" class="btn btn-ghost btn-sm" onclick="navigateHistoryPage(-1)">◀ Prev</button>
        <button id="btnHistNext" class="btn btn-ghost btn-sm" onclick="navigateHistoryPage(1)">Next ▶</button>
      </div>
    </div>

  </div>
</div>

</main> <div class="modal-overlay" id="emergencyModal">
  <div class="modal-box modal-danger">
    <div class="modal-icon">🚨</div>
    <h3>Activate Emergency Alert?</h3>
    <p>This will <strong>force-activate ALL actuators</strong> (pump, buzzer, fan) and log a critical incident immediately.</p>
    
    <div style="margin: 20px 0; text-align: left;">
      <label for="emergencyPasswordInput" style="display:block; margin-bottom:8px; font-weight:600; font-size:0.9rem; color: var(--text-muted);">Confirm Identity Account Password:</label>
      <input type="password" id="emergencyPasswordInput" placeholder="••••••••" style="padding: 12px; width: 100%; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); background: rgba(0,0,0,0.2); color: #fff; box-sizing: border-box; font-size: 1rem;">
    </div>

    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-danger" onclick="confirmEmergency()">Yes — Activate</button>
    </div>
  </div>
</div>

<script>
  const USER_ID = <?= $userId ?>;
</script>
<script src="assets/js/script.js"></script>
</body>
</html>