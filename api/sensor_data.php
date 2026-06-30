<?php
// ============================================================
//  api/sensor_data.php
//  Receives POST from Arduino, stores reading, returns JSON.
// ============================================================
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

$db = getDB();

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
    // ── FIXED Environment is clear ──
    // If the last registered log was a danger state, register a 'clear' state entry to reset the cycle chain!
    if (in_array($realLastIncidentType, ['fire', 'gas', 'temp', 'emergency'])) {
        $incidentType = 'clear';
    } else {
        $incidentType = 'clear'; // Keeps it clear default state
    }
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
    // Only perform automatic hardware stepdown reset adjustments if manual override tracking is turned off!
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
// We don't save raw 'clear' states if the last state was already 'clear' to prevent flooding.
// But transitioning from 'fire' to 'clear' will log successfully, freeing up the next 'fire' event!
if ($incidentType !== null && $incidentType !== $realLastIncidentType) {
    
    // Skip logging a flat 'clear' if it's just repeating baseline records
    if ($incidentType === 'clear' && ($realLastIncidentType === 'clear' || $realLastIncidentType === '')) {
        // Do nothing
    } else {
        $userId = 1; 
        if (isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        } else {
            $userCheck = $db->query('SELECT id FROM users LIMIT 1')->fetch_assoc();
            if ($userCheck) { $userId = (int) $userCheck['id']; }
        }

        $ins = $db->prepare(
            'INSERT INTO incidents (user_id, incident_type, temperature, humidity, gas_level, flame_detected)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->bind_param('isddii', $userId, $incidentType, $temp, $hum, $gas, $flame);
        $ins->execute();
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