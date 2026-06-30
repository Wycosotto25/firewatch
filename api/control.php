<?php
// ============================================================
//  api/control.php
//  GET  → return current actuator state (Arduino polls this)
//  POST → update actuator state (dashboard JS calls this)
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$db = getDB();

// ══ GET — Arduino polls for commands ════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $row = $db->query(
        'SELECT pump, buzzer, fan, emergency FROM actuator_state ORDER BY id DESC LIMIT 1'
    )->fetch_assoc() ?? ['pump' => 0, 'buzzer' => 0, 'fan' => 0, 'emergency' => 0];

    echo json_encode([
        'pump'      => (int) $row['pump'],
        'buzzer'    => (int) $row['buzzer'],
        'fan'       => (int) $row['fan'],
        'emergency' => (int) $row['emergency'],
    ]);
    exit;
}

// ══ POST — Dashboard updates actuator state ══════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    $body = file_get_contents('php://input');
    $data = json_decode($body, true) ?? [];

    if (empty($data)) { $data = $_POST; }

    $action = $data['action'] ?? '';

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];

    // ── Emergency: force all ON (With Password Verification) ──
    if ($action === 'emergency') {
        $password = $data['password'] ?? '';

        if (empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Password is required to declare an Emergency.']);
            exit;
        }

        // Retrieve current active hash from database tracking parameters
        $userStmt = $db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!$userResult || !password_verify($password, $userResult['password'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid administrative credentials. Authorization denied.']);
            exit;
        }

        // Force database rules array to active flag positions
        $db->query('UPDATE actuator_state SET pump=1, buzzer=1, fan=1, emergency=1');

        $sensor = $db->query(
            'SELECT temperature, humidity, gas_level, flame_detected
             FROM sensor_data ORDER BY recorded_at DESC LIMIT 1'
        )->fetch_assoc() ?? ['temperature' => 0, 'humidity' => 0, 'gas_level' => 0, 'flame_detected' => 0];

        $type = 'emergency';
        $ins  = $db->prepare(
            'INSERT INTO incidents (user_id, incident_type, temperature, humidity, gas_level, flame_detected)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->bind_param('isddii', $userId, $type, $sensor['temperature'], $sensor['humidity'], $sensor['gas_level'], $sensor['flame_detected']);
        $ins->execute();
        $ins->close();

        echo json_encode([
            'status'  => 'ok',
            'action'  => 'emergency',
            'pump'    => 1, 'buzzer'  => 1, 'fan'     => 1, 'emergency' => 1
        ]);
        exit;
    }

    // ── Reset emergency flag / Turning OFF Emergency ──────────
    if ($action === 'reset') {
        $db->query('UPDATE actuator_state SET pump=0, buzzer=0, fan=0, emergency=0');
        
        // Log manual de-escalation actions
        $type = 'manual';
        $sensor = $db->query('SELECT temperature, humidity, gas_level, flame_detected FROM sensor_data ORDER BY recorded_at DESC LIMIT 1')->fetch_assoc();
        
        $ins = $db->prepare('INSERT INTO incidents (user_id, incident_type, temperature, humidity, gas_level, flame_detected) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->bind_param('isddii', $userId, $type, $sensor['temperature'], $sensor['humidity'], $sensor['gas_level'], $sensor['flame_detected']);
        $ins->execute();
        $ins->close();

        echo json_encode([
            'status' => 'ok', 
            'message' => 'All emergency actuators successfully disarmed and reset.',
            'pump' => 0, 'buzzer' => 0, 'fan' => 0, 'emergency' => 0
        ]);
        exit;
    }

    // ── Individual actuator toggle ────────────────────────────
    if ($action === 'set') {
        $allowed = ['pump', 'buzzer', 'fan'];
        $device  = $data['device'] ?? '';
        $value   = isset($data['value']) ? (int)(bool) $data['value'] : 0;

        if (!in_array($device, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid device target.']);
            exit;
        }

        $db->query("UPDATE actuator_state SET `{$device}` = {$value}");

        if ($value === 1) {
            $sensor = $db->query('SELECT temperature, humidity, gas_level, flame_detected FROM sensor_data ORDER BY recorded_at DESC LIMIT 1')->fetch_assoc();
            $type = 'manual';
            $ins  = $db->prepare('INSERT INTO incidents (user_id, incident_type, temperature, humidity, gas_level, flame_detected) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->bind_param('isddii', $userId, $type, $sensor['temperature'], $sensor['humidity'], $sensor['gas_level'], $sensor['flame_detected']);
            $ins->execute();
            $ins->close();
        }

        $row = $db->query('SELECT pump, buzzer, fan, emergency FROM actuator_state ORDER BY id DESC LIMIT 1')->fetch_assoc();
        echo json_encode([
            'status' => 'ok',
            'pump'   => (int) $row['pump'],
            'buzzer' => (int) $row['buzzer'],
            'fan'    => (int) $row['fan'],
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action request.']);
    exit;
}