<?php
// ============================================================
//   api/sensor_data.php
//   Receives POST from Arduino, stores reading, returns JSON.
// ============================================================
if (!is_dir('/tmp/sessions')) { mkdir('/tmp/sessions', 0777, true); }
ini_set('session.save_path', '/tmp/sessions');
session_start(); 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── Parse Input ──────────────────────────────────────────────
$body = file_get_contents('php://input');
$json = json_decode($body, true);

if ($json) {
    $temp  = $json['temperature']    ?? null;
    $hum   = $json['humidity']       ?? null;
    $gas   = $json['gas_level']      ?? null;
    $flame = $json['flame_detected'] ?? null;
} else {
    $temp  = $_POST['temperature']    ?? null;
    $hum   = $_POST['humidity']       ?? null;
    $gas   = $_POST['gas_level']      ?? null;
    $flame = $_POST['flame_detected'] ?? null;
}

if ($temp === null || $hum === null || $gas === null || $flame === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$temp  = (float) $temp;
$hum   = (float) $hum;
$gas   = (int)   $gas;
$flame = (int)   $flame ? 1 : 0;

$db = $conn;

// ── Store Sensor Reading ──────────────────────────────────────
$stmt = $db->prepare(
    'INSERT INTO sensor_data (temperature, humidity, gas_level, flame_detected)
     VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('ddii', $temp, $hum, $gas, $flame);
$stmt->execute();

// ── Web Actuator Initialization & Current State Lookup ───────
$currentActuator = $db->query(
    'SELECT pump, buzzer, fan, emergency, manual_override FROM actuator_state ORDER BY id DESC LIMIT 1'
)->fetch_assoc() ?? ['pump' => 0, 'buzzer' => 0, 'fan' => 0, 'emergency' => 0, 'manual_override' => 0];

// Fetch the absolute last logged incident directly from DB
$lastLogQuery = $db->query('SELECT incident_type FROM incidents ORDER BY id DESC LIMIT 1')->fetch_assoc();
$realLastIncidentType = $lastLogQuery ? trim(strtolower($lastLogQuery['incident_type'])) : '';

// ── 🚨 CONDITIONAL LOGIC TRUTH TABLE MATRIX ───────────────────
$TEMP_FAN_ACTIVATE = 35.0;  // 35°C turns on the physical fan
$GAS_WARN          = 300;   // 300 PPM turns on the physical fan

$incidentType = null; 
$forcedPump   = 0;
$forcedBuzzer = 0;
$forcedFan    = 0;
$isAutomatedTrigger = false;

if ((int)$currentActuator['emergency'] === 1) {
    $incidentType       = 'emergency';
    $forcedPump         = 1;
    $forcedBuzzer       = 1;
    $forcedFan          = 1;
    $isAutomatedTrigger = true;
} elseif ($flame === 1 && ($gas >= $GAS_WARN || $temp >= $TEMP_FAN_ACTIVATE)) {
    $incidentType       = 'fire';
    $forcedPump         = 1;
    $forcedBuzzer       = 1;
    $forcedFan          = 1;
    $isAutomatedTrigger = true;
} elseif ($flame === 1) {
    $incidentType       = 'fire';
    $forcedPump         = 1;
    $forcedBuzzer       = 1;
    $forcedFan          = 0;
    $isAutomatedTrigger = true;
} elseif ($gas >= $GAS_WARN && $temp >= $TEMP_FAN_ACTIVATE) {
    $incidentType       = 'gas'; 
    $forcedPump         = 0;
    $forcedBuzzer       = 1;
    $forcedFan          = 1;
    $isAutomatedTrigger = true;
} elseif ($gas >= $GAS_WARN) {
    $incidentType       = 'gas';
    $forcedPump         = 0;
    $forcedBuzzer       = 0;
    $forcedFan          = 1;
    $isAutomatedTrigger = true;
} elseif ($temp >= $TEMP_FAN_ACTIVATE) {
    $incidentType       = 'temp'; 
    $forcedPump         = 0;
    $forcedBuzzer       = 0;
    $forcedFan          = 1;
    $isAutomatedTrigger = true;
} else {
    // ── Environment is clear ──
    $incidentType = 'clear';
}

// ── Web Actuator Synchronization Hub ──────────────────────────
if ($isAutomatedTrigger) {
    if ((int)$currentActuator['pump'] !== $forcedPump || 
        (int)$currentActuator['buzzer'] !== $forcedBuzzer || 
        (int)$currentActuator['fan'] !== $forcedFan) {
        
        $syncActuator = $db->prepare(
            'INSERT INTO actuator_state (pump, buzzer, fan, emergency, manual_override) VALUES (?, ?, ?, ?, 0)'
        );
        $emergencyFlag = (int)$currentActuator['emergency']; 
        $syncActuator->bind_param('iiii', $forcedPump, $forcedBuzzer, $forcedFan, $emergencyFlag);
        $syncActuator->execute();
    }
    $actuator = [
        'pump' => $forcedPump, 
        'buzzer' => $forcedBuzzer, 
        'fan' => $forcedFan, 
        'emergency' => (int)$currentActuator['emergency'],
        'manual_override' => 0
    ];
} else {
    if ((int)$currentActuator['manual_override'] === 0 && (int)$currentActuator['emergency'] === 0 && 
       ((int)$currentActuator['pump'] !== 0 || (int)$currentActuator['buzzer'] !== 0 || (int)$currentActuator['fan'] !== 0)) {
        
        $resetVal = 0;
        $syncActuator = $db->prepare(
            'INSERT INTO actuator_state (pump, buzzer, fan, emergency, manual_override) VALUES (?, ?, ?, ?, 0)'
        );
        $syncActuator->bind_param('iiii', $resetVal, $resetVal, $resetVal, $resetVal);
        $syncActuator->execute();

        $actuator = ['pump' => 0, 'buzzer' => 0, 'fan' => 0, 'emergency' => 0, 'manual_override' => 0];
    } else {
        $actuator = $currentActuator;
    }
}

// ── 🛡️ DATABASE STATE LOGGING CONTROL ENGINE ───────────────────
// NOTE: wrapped in try/catch. This INSERT is what was crashing the
// whole endpoint with a 500 error — the 'incident_type' column was
// rejecting the value 'clear' (truncation error under mysqli strict
// mode). The real fix is the database column itself: see
// fix_incident_type_column.sql, which widens incident_type to
// VARCHAR(20) so every value this script writes ('fire', 'gas',
// 'temp', 'manual', 'emergency', 'clear') fits.
//
// This try/catch is a second layer of defense on top of that fix:
// even if some future incident_type value doesn't fit the column,
// the sensor reading already stored above will still succeed, and
// the response below will still return valid JSON instead of a 500.
if ($incidentType !== null && $incidentType !== $realLastIncidentType) {
    if ($incidentType === 'clear' && ($realLastIncidentType === 'clear' || $realLastIncidentType === '')) {
        // Do nothing — no need to log a redundant "still clear" entry.
    } else {
        $userId = 1; 
        if (isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        } else {
            $userCheck = $db->query('SELECT id FROM users LIMIT 1')->fetch_assoc();
            if ($userCheck) { $userId = (int) $userCheck['id']; }
        }

        try {
            $ins = $db->prepare(
                'INSERT INTO incidents (user_id, incident_type, temperature, humidity, gas_level, flame_detected)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->bind_param('isddii', $userId, $incidentType, $temp, $hum, $gas, $flame);
            $ins->execute();
        } catch (mysqli_sql_exception $e) {
            // Log the failure but do not let it take down the whole
            // request — the sensor reading was already saved above,
            // and the dashboard still needs valid JSON back.
            error_log(
                'Failed to log incident (type=' . $incidentType . '): ' . $e->getMessage()
            );
        }
    }
}

// ── Response JSON ────────────────────────────────────────────
echo json_encode([
    'status'   => 'ok',
    'stored'   => true,
    'incident' => $incidentType,
    'actuator' => [
        'pump'            => (int) $actuator['pump'],
        'buzzer'          => (int) $actuator['buzzer'],
        'fan'             => (int) $actuator['fan'],
        'emergency'       => (int) $actuator['emergency'],
        'manual_override' => (int) $actuator['manual_override']
    ]
]);
