// ============================================================
//   assets/js/script.js  –  FIREWATCH Dashboard Logic
// ============================================================

'use strict';

const TEMP_WARN     = 30;   
const TEMP_DANGER   = 35;   
const GAS_WARN      = 300;  
const GAS_DANGER    = 400;  
const POLL_INTERVAL = 3000; 

const els = {
  statusDot:   document.getElementById('statusDot'),
  statusText:  document.getElementById('statusText'),
  statusTime:  document.getElementById('statusTime'),
  alertBadge:  document.getElementById('alertBadge'),
  alertText:   document.getElementById('alertBadgeText'),

  valTemp:  document.getElementById('val-temp'),
  valHum:   document.getElementById('val-hum'),
  valGas:   document.getElementById('val-gas'),
  valFlame: document.getElementById('val-flame'),

  stTemp:  document.getElementById('st-temp'),
  stHum:   document.getElementById('st-hum'),
  stGas:   document.getElementById('st-gas'),
  stFlame: document.getElementById('st-flame'),

  cardTemp:  document.getElementById('card-temp'),
  cardHum:   document.getElementById('card-hum'),
  cardGas:   document.getElementById('card-gas'),
  cardFlame: document.getElementById('card-flame'),

  togPump:   document.getElementById('tog-pump'),
  togBuzzer: document.getElementById('tog-buzzer'),
  togFan:    document.getElementById('tog-fan'),

  logBody:        document.getElementById('logBody'),
  emergencyModal: document.getElementById('emergencyModal'),
  emergencyBtn:   document.getElementById('emergencyBtn'),
  emergencyNote:  document.getElementById('emergencyNote'),
};

let emergencyActive = false;
let isUpdatingActuator = false; 
let serialWriter = null;        

// ══ NEW CORE GLOBAL VARIABLES FOR LOG HISTORY ════════════════
let currentHistoryPage = 1;
let totalHistoryPages = 1;

// Global string escaping utility helper to prevent processing layout crashes
function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Ensure functions are declared globally in window scope for any inline HTML bindings
window.openHistoryModal = openHistoryModal;
window.closeHistoryModal = closeHistoryModal;
window.fetchHistoryLogs = fetchHistoryLogs;
window.navigateHistoryPage = navigateHistoryPage;

function openHistoryModal() {
    const modal = document.getElementById('historyLogModal');
    if (modal) {
        // Remove any conflicting inline display attributes
        modal.style.setProperty('display', 'flex', 'important');
        // Add the correct visualization class hook
        modal.classList.add('open');
        
        // Execute your pagination/data retrieval engine
        if (typeof fetchHistoryLogs === 'function') {
            fetchHistoryLogs(1);
        }
    }
}

function closeHistoryModal() {
    const modal = document.getElementById('historyLogModal');
    if (modal) {
        modal.classList.remove('open');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 250); // Matches smooth transition curves
    }
}

async function fetchHistoryLogs(page = 1) {
  currentHistoryPage = page;
  
  const typeEl = document.getElementById('histFilterType');
  const userEl = document.getElementById('histSearchUser');
  const fromEl = document.getElementById('histDateFrom');
  const toEl   = document.getElementById('histDateTo');

  const type = typeEl ? typeEl.value : '';
  const user = userEl ? userEl.value : '';
  const from = fromEl ? fromEl.value : '';
  const to   = toEl ? toEl.value : '';

  const url = `api/get_all_logs.php?page=${page}&limit=10&type=${encodeURIComponent(type)}&user=${encodeURIComponent(user)}&from=${from}&to=${to}`;

  try {
    const response = await fetch(url, { cache: 'no-store' });
    if (!response.ok) throw new Error("Network query invalid.");
    const data = await response.json();

    totalHistoryPages = data.totalPages || 1;
    renderHistoryTable(data.logs);
    
    const infoEl = document.getElementById('historyPaginationInfo');
    if (infoEl) {
      infoEl.textContent = `Showing page ${data.currentPage} of ${totalHistoryPages} (Total: ${data.totalRecords} records)`;
    }
    
    const btnPrev = document.getElementById('btnHistPrev');
    const btnNext = document.getElementById('btnHistNext');
    if (btnPrev) btnPrev.disabled = (currentHistoryPage <= 1);
    if (btnNext) btnNext.disabled = (currentHistoryPage >= totalHistoryPages);

  } catch (err) {
    console.error("Failed parsing log matrix:", err);
  }
}

function renderHistoryTable(logs) {
  const tbody = document.getElementById('historyLogTableBody');
  if (!tbody) return;

  if (!logs || logs.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color:rgba(255,255,255,0.4);">No historical logs matching query constraints.</td></tr>`;
    return;
  }

  // Generate the table HTML structure
  const rowsHtml = logs.map(r => {
    // 1. Clean the incoming database string
    let typeClean = r.incident_type ? r.incident_type.trim().toLowerCase() : '';
    
    // 2. Identify structural blanks/normals using the flame sensor logic
    if (typeClean === '' || typeClean === 'normal') {
      typeClean = parseInt(r.flame_detected, 10) === 1 ? 'fire' : 'clear';
    }
    
    // 3. CORE CHANGE: If the calculated status is 'clear', do not display the data row
    if (typeClean === 'clear') {
      return '';
    }
    
    // 4. Assign style badge classes for actual incidents (Fire, Emergency, Gas, Temp, Manual)
    let badgeClass = 'badge-secondary';
    if (typeClean === 'fire' || typeClean === 'emergency') badgeClass = 'badge-danger';
    else if (typeClean === 'gas' || typeClean === 'temp') badgeClass = 'badge-warning';
    else if (typeClean === 'manual') badgeClass = 'badge-info';

    const tempVal = r.temperature ? parseFloat(r.temperature).toFixed(2) : '0.00';
    const humVal  = r.humidity ? parseFloat(r.humidity).toFixed(2) : '0.00';
    const gasVal  = r.gas_level ? parseInt(r.gas_level, 10) : '0';
    const flameStatus = parseInt(r.flame_detected, 10) ? '🔥 YES' : '✅ NO';

    return `
      <tr>
        <td>${r.id}</td>
        <td>${esc(r.fullname)}</td>
        <td><span class="badge ${badgeClass}">${typeClean.toUpperCase()}</span></td>
        <td>${tempVal} °C</td>
        <td>${humVal} %</td>
        <td>${gasVal} PPM</td>
        <td>${flameStatus}</td>
        <td>${r.created_at}</td>
      </tr>
    `;
  }).join('');

  // 5. Check if all items on this page were filtered out as "clear" rows
  if (rowsHtml.trim() === '') {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color:rgba(255,255,255,0.4);">No active system incidents on this data page.</td></tr>`;
  } else {
    tbody.innerHTML = rowsHtml;
  }
}

function navigateHistoryPage(direction) {
  const targetPage = currentHistoryPage + direction;
  if (targetPage >= 1 && targetPage <= totalHistoryPages) {
    fetchHistoryLogs(targetPage);
  }
}

// ══ POLLING FUNCTION (WITH ANTI-STALE UNPLUGGED DETECTION) ════════
async function pollSensors() {
  if (isUpdatingActuator) return; 

  try {
    const res  = await fetch('api/get_sensor.php', { cache: 'no-store' });
    const data = await res.json();

    if (data.error) { setStatus('error', data.error); return; }

    if (data.sensor && data.sensor.recorded_at) {
      const lastUpdate = new Date(data.sensor.recorded_at.replace(/-/g, "/")).getTime();
      const nowTime    = new Date().getTime();
      
      if (nowTime - lastUpdate > 6000) {
        setStatus('warn', '❌ Hardware Offline — Arduino stream disconnected.');
        setToStaleState();
        updateActuators(data.actuator);
        updateLog(data.logs);
        return; 
      }
    }

    updateSensors(data.sensor);
    updateActuators(data.actuator);
    updateLog(data.logs);

  } catch (err) {
    setStatus('error', 'Lost connection to server — retrying…');
    console.error('Poll error:', err);
  }
}

function setToStaleState() {
  if (els.valTemp)  els.valTemp.textContent = '--';
  if (els.valHum)   els.valHum.textContent = '--';
  if (els.valGas)   els.valGas.textContent = '--';
  if (els.valFlame) els.valFlame.textContent = 'OFFLINE';
  setBadge('normal', '🔌 UNPLUGGED');
}

function updateSensors(s) {
  if (!s || s.temperature === null || s.temperature === '--') {
    setStatus('warn', 'No sensor data yet — waiting for Arduino…');
    return;
  }

  const temp  = parseFloat(s.temperature);
  const hum   = parseFloat(s.humidity);
  const gas   = parseInt(s.gas_level, 10);
  const flame = parseInt(s.flame_detected, 10) === 1;

  if (els.valTemp) els.valTemp.textContent = isNaN(temp) ? '--' : temp.toFixed(1);
  applyLevel(els.cardTemp, els.stTemp,
    temp >= TEMP_DANGER ? 'danger' :
    temp >= TEMP_WARN   ? 'warning' : 'normal',
    temp >= TEMP_DANGER ? '🔴 HIGH' :
    temp >= TEMP_WARN   ? '🟡 ELEVATED' : '🟢 NORMAL'
  );

  if (els.valHum) els.valHum.textContent = isNaN(hum) ? '--' : hum.toFixed(1);
  applyLevel(els.cardHum, els.stHum, 'normal',
    hum < 20 ? '⚠️ LOW' : hum > 80 ? '⚠️ HIGH' : '🟢 NORMAL'
  );

  if (els.valGas) els.valGas.textContent = isNaN(gas) ? '--' : gas;
  applyLevel(els.cardGas, els.stGas,
    gas >= GAS_DANGER ? 'danger' :
    gas >= GAS_WARN   ? 'warning' : 'normal',
    gas >= GAS_DANGER ? '🔴 DANGER' :
    gas >= GAS_WARN   ? '🟡 ELEVATED' : '🟢 CLEAR'
  );

  if (els.valFlame) els.valFlame.textContent = flame ? 'DETECTED' : 'CLEAR';
  applyLevel(els.cardFlame, els.stFlame,
    flame ? 'danger' : 'normal',
    flame ? '🔴 FIRE DETECTED' : '🟢 NO FLAME'
  );

  if (!emergencyActive) {
    if (flame && temp >= TEMP_DANGER && gas >= GAS_DANGER) {
      setBadge('fire',    '🔥 CRITICAL FIRE');
      setStatus('danger', 'CRITICAL: Flame + high temperature + smoke detected!');
    } else if (flame || (temp >= TEMP_DANGER && gas >= GAS_DANGER)) {
      setBadge('danger',  '🚨 ALERT');
      setStatus('danger', 'Alert: Fire conditions detected — actuators responding.');
    } else if (gas >= GAS_WARN || temp >= TEMP_WARN) {
      setBadge('warning', '⚠️ WARNING');
      setStatus('warn',   'Warning: Elevated sensor readings.');
    } else {
      setBadge('normal',  '✅ MONITORING');
      setStatus('ok',     'All sensors nominal.');
    }
  }

  if (els.statusTime) {
    els.statusTime.textContent = `Last update: ${new Date().toLocaleTimeString()}`;
  }
}

function applyLevel(card, statusEl, level, label) {
  if (!card || !statusEl) return;
  card.classList.remove('warning', 'danger', 'active');
  if (level === 'warning') card.classList.add('warning');
  if (level === 'danger')  card.classList.add('danger');
  if (level !== 'normal')  card.classList.add('active');
  statusEl.textContent = label;
}

function updateActuators(a) {
  if (!a) return;
  silentCheck(els.togPump,   !!parseInt(a.pump));
  silentCheck(els.togBuzzer, !!parseInt(a.buzzer));
  silentCheck(els.togFan,    !!parseInt(a.fan));

  const isEmergency = parseInt(a.emergency, 10) === 1;
  
  if (isEmergency && !emergencyActive) {
    emergencyActive = true;
    if (els.emergencyBtn) {
      els.emergencyBtn.classList.add('active');
      els.emergencyBtn.textContent = '❌ TURN OFF EMERGENCY';
    }
    if (els.emergencyNote) els.emergencyNote.textContent = '⚠️ EMERGENCY MODE ACTIVE';
    setBadge('danger', '🚨 EMERGENCY');
  } 
  else if (!isEmergency && emergencyActive) {
    emergencyActive = false;
    if (els.emergencyBtn) {
      els.emergencyBtn.classList.remove('active');
      els.emergencyBtn.textContent = '🚨 TRIGGER EMERGENCY MODE';
    }
    if (els.emergencyNote) els.emergencyNote.textContent = 'Clicking will instantly engage all fire mitigation systems';
    setBadge('normal', '✅ MONITORING');
  }
}

function silentCheck(input, val) {
  if (!input) return;
  const prev = input.onchange;
  input.onchange = null;
  input.checked = val;
  input.onchange = prev;
}

function updateLog(logs) {
  if (!logs || !logs.length || !els.logBody) return;

  const rows = logs.map(r => {
    const typeClean = r.incident_type ? r.incident_type.trim().toLowerCase() : '';
    
    if (typeClean === '' || typeClean === 'normal') return '';

    let badgeClass = 'badge-secondary';
    if (typeClean === 'fire' || typeClean === 'emergency') {
      badgeClass = 'badge-danger';      
    } else if (typeClean === 'gas' || typeClean === 'temp') {
      badgeClass = 'badge-warning';     
    } else if (typeClean === 'manual') {
      badgeClass = 'badge-info';        
    }

    const tempVal = r.temperature ? parseFloat(r.temperature).toFixed(2) : '0.00';
    const humVal  = r.humidity ? parseFloat(r.humidity).toFixed(2) : '0.00';
    const gasVal  = r.gas_level ? parseInt(r.gas_level, 10) : '0';
    const flameStatus = parseInt(r.flame_detected, 10) ? '🔥 YES' : '✅ NO';

    return `
       <tr>
         <td>${r.id}</td>
         <td>${esc(r.fullname)}</td>
         <td><span class="badge ${badgeClass}">${typeClean.toUpperCase()}</span></td>
         <td>${tempVal} °C</td>
         <td>${humVal} %</td>
         <td>${gasVal} PPM</td>
         <td>${flameStatus}</td>
         <td>${r.created_at}</td>
       </tr>
    `;
  }).join('');

  els.logBody.innerHTML = rows;
}

function setActuator(type, state) {
  isUpdatingActuator = true; 

  if (serialWriter) {
    let arduinoTargetName = '';
    if (type === 'pump')    arduinoTargetName = 'SPRINKLER_SYSTEM';
    if (type === 'fan')     arduinoTargetName = 'VENTILATION_FAN';
    if (type === 'buzzer')  arduinoTargetName = 'SIREN_BUZZER';

    if (arduinoTargetName) {
      const commandString = `${arduinoTargetName}:${state ? 'ON' : 'OFF'}\n`;
      serialWriter.write(commandString).catch(err => console.error("USB Write error:", err));
    }
  }

  fetch('api/control.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'set',
      device: type,
      value: state ? 1 : 0
    })
  })
    .then(res => { if (!res.ok) throw new Error(); return res.json(); })
    .then(data => {
      if (data.status !== 'ok') {
        alert("Failed to update system state.");
        document.getElementById('tog-' + type).checked = !state; 
      }
    })
    .catch(() => {
      alert("Failed to save changes to backend database configuration.");
      document.getElementById('tog-' + type).checked = !state; 
    })
    .finally(() => {
      setTimeout(() => { isUpdatingActuator = false; }, 1200);
    });
}

function triggerEmergency() {
  if (emergencyActive) {
    if (confirm("Are you sure you want to disarm emergency mode and return to local automated rules?")) {
      disableEmergency();
    }
  } else {
    openModal();
  }
}

function openModal() { if (els.emergencyModal) els.emergencyModal.classList.add('open'); }
function closeModal() { if (els.emergencyModal) els.emergencyModal.classList.remove('open'); }

async function confirmEmergency() {
  const passwordInput = document.getElementById('emergencyPasswordInput');
  const password = passwordInput ? passwordInput.value : '';

  if (!password) {
    alert("Administrative password authentication entry required.");
    return;
  }

  closeModal();
  if (passwordInput) passwordInput.value = ''; 

  try {
    const res = await fetch('api/control.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'emergency', password: password })
    });
    
    if (!res.ok) {
      const errData = await res.json();
      throw new Error(errData.error || "Authentication verification failure.");
    }

    const data = await res.json();

    if (data.status === 'ok') {
      if (serialWriter) {
        await serialWriter.write("EMERGENCY_SYSTEM:ON\n");
      }

      emergencyActive = true;
      if (els.emergencyBtn) {
        els.emergencyBtn.classList.add('active');
        els.emergencyBtn.textContent = '❌ TURN OFF EMERGENCY';
      }
      setStatus('danger', 'EMERGENCY MODE DECLARED MANUALLY.');
      await pollSensors();
    }
  } catch (err) {
    alert(err.message);
  }
}

async function disableEmergency() {
  try {
    const res = await fetch('api/control.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reset' })
    });
    const data = await res.json();

    if (data.status === 'ok') {
      if (serialWriter) {
        await serialWriter.write("EMERGENCY_SYSTEM:OFF\n");
        await serialWriter.write("RESET_AUTOMATION\n");
      }

      emergencyActive = false;
      if (els.emergencyBtn) {
        els.emergencyBtn.classList.remove('active');
        els.emergencyBtn.textContent = '🚨 TRIGGER EMERGENCY MODE';
      }
      setStatus('ok', 'Emergency clear instructions logged.');
      await pollSensors();
    }
  } catch (err) {
    console.error(err);
    alert('Failed to transmit disarm command request.');
  }
}

function setupArduinoSerial() {
  const connBtn = document.getElementById('arduinoConnectBtn');
  if (!connBtn) return;

  navigator.serial.addEventListener('disconnect', (event) => {
    console.warn("Hardware device physically disconnected from port:", event.target);
    connBtn.textContent = '🔌 Connect Arduino';
    connBtn.className = 'btn btn-primary btn-sm';
    connBtn.disabled = false;
    serialWriter = null;
    setStatus('warn', '❌ Hardware Offline — Arduino stream disconnected.');
    setToStaleState();
  });

  connBtn.addEventListener('click', async () => {
    if (!('serial' in navigator)) {
      alert('Your browser does not support Web Serial connections. Use Google Chrome or Edge.');
      return;
    }

    try {
      const port = await navigator.serial.requestPort();
      await port.open({ baudRate: 9600 });
      
      setStatus('ok', '🔌 Web Serial pipeline connected to hardware.');
      connBtn.textContent = '✅ Arduino Connected';
      connBtn.className = 'btn btn-ghost btn-sm';
      connBtn.disabled = true;

      const textEncoder = new TextEncoderStream();
      textEncoder.readable.pipeTo(port.writable);
      serialWriter = textEncoder.writable.getWriter();

      const textDecoder = new TextDecoderStream();
      port.readable.pipeTo(textDecoder.writable);
      const reader = textDecoder.readable.getReader();

      let serialBuffer = '';
      
      while (true) {
        const { value, done } = await reader.read();
        if (done) { reader.releaseLock(); break; }
        
        serialBuffer += value;
        let lines = serialBuffer.split('\n');
        serialBuffer = lines.pop(); 

        for (let rawLine of lines) {
          rawLine = rawLine.trim();
          if (rawLine.startsWith("TEMP:")) {
            parseAndSaveSerialData(rawLine);
          }
        }
      }

    } catch (err) {
      console.error('Serial port interface failure:', err);
      setStatus('warn', 'Arduino connection canceled.');
      connBtn.textContent = '🔌 Connect Arduino';
      connBtn.className = 'btn btn-primary btn-sm';
      connBtn.disabled = false;
    }
  });
}

function parseAndSaveSerialData(line) {
  try {
    const tokens = line.split(',');
    let parsedData = {};
    
    tokens.forEach(token => {
      const parts = token.split(':');
      if (parts.length === 2) parsedData[parts[0]] = parts[1];
    });

    const temp   = parsedData['TEMP'];
    const humid  = parsedData['HUMID'];
    const gas    = parsedData['GAS'];
    const flameRaw = parseInt(parsedData['FLAME'] || 1023, 10);
    const flameDetected = (flameRaw < 400) ? 1 : 0;

    if (temp !== undefined && humid !== undefined && gas !== undefined) {
      fetch('api/sensor_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          temperature: temp,
          humidity: humid,
          gas_level: gas,
          flame_detected: flameDetected
        })
      })
      .then(async (res) => {
        const resData = await res.json();
        if (!res.ok || resData.error) {
           console.warn("Database rejected data:", resData);
        }
      })
      .catch(e => console.error("Network upload error:", e));
    }
  } catch (err) {
    console.error("Failed to parse incoming hardware string:", err);
  }
}

function setStatus(level, message) {
  if (!els.statusDot || !els.statusText) return;
  els.statusDot.className   = 'status-dot ' + level;
  els.statusText.textContent = message;
}

function setBadge(level, text) {
  if (!els.alertBadge || !els.alertText) return;
  els.alertBadge.className   = 'welcome-badge ' + level;
  els.alertText.textContent  = text;
}

function setupThemeSwitcher() {
  const themeToggleBtn = document.getElementById('themeToggle');
  const themeToggleIcon = document.getElementById('themeToggleIcon');
  if (!themeToggleBtn || !themeToggleIcon) return; 

  const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
  themeToggleIcon.textContent = currentTheme === 'light' ? '☀️' : '🌙';

  themeToggleBtn.addEventListener('click', function() {
    const activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    const newTheme = activeTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('firewatch-theme', newTheme);
    themeToggleIcon.textContent = newTheme === 'light' ? '☀️' : '🌙';
  });
}

// ══ INITIALIZE ═══════════════════════════════════════════════
(function init() {
  if (els.statusDot) {
    setStatus('ok', 'Connecting to sensor network…');
    pollSensors();
    setInterval(pollSensors, POLL_INTERVAL);
  }
  
  setupThemeSwitcher();
  setupArduinoSerial();

  if (els.emergencyBtn) {
    els.emergencyBtn.onclick = triggerEmergency;
  }
  
  const modalConfirmBtn = document.querySelector('#emergencyModal .btn-danger');
  if (modalConfirmBtn) {
    modalConfirmBtn.onclick = confirmEmergency;
  }

  const modalCancelBtn = document.querySelector('#emergencyModal .btn-ghost');
  if (modalCancelBtn) {
    modalCancelBtn.onclick = closeModal;
  }

  // Bind History Log elements inside runtime stack safely
  const historyBtn = document.querySelector("button[onclick='openHistoryModal()']");
  if (historyBtn) {
    historyBtn.removeAttribute("onclick");
    historyBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openHistoryModal();
    });
  }
})();