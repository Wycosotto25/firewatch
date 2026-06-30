<?php
// ============================================================
//  api/get_sensor.php  –  Dashboard AJAX polling endpoint
//  Returns latest sensor data + actuator state + recent logs
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
$db = getDB();

// Latest sensor
$sensor = $db->query(
    'SELECT * FROM sensor_data ORDER BY recorded_at DESC LIMIT 1'
)->fetch_assoc() ?? [
    'temperature' => null, 'humidity' => null,
    'gas_level' => null, 'flame_detected' => 0, 'recorded_at' => null
];

// Actuator state including manual_override tracker 
$actuator = $db->query(
    'SELECT pump, buzzer, fan, emergency, manual_override FROM actuator_state ORDER BY id DESC LIMIT 1'
)->fetch_assoc() ?? ['pump' => 0, 'buzzer' => 0, 'fan' => 0, 'emergency' => 0, 'manual_override' => 0];

// Last 20 incidents
$logs = [];
$res  = $db->query(
    'SELECT i.*, u.fullname
     FROM incidents i
     JOIN users u ON u.id = i.user_id
     ORDER BY i.created_at DESC
     LIMIT 20'  
);
while ($row = $res->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'sensor'   => $sensor,
    'actuator' => $actuator,
    'logs'     => $logs,
]);