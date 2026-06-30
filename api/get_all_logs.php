<?php
// ============================================================
//  api/get_all_logs.php  –  Paginated Historical Logs Engine
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

// ── GET Parameters ───────────────────────────────────────────
$page   = isset($_GET['page'])   ? max(1, (int)$_GET['page'])   : 1;
$limit  = isset($_GET['limit'])  ? max(1, (int)$_GET['limit'])  : 10;
$offset = ($page - 1) * $limit;

$filterType = isset($_GET['type']) ? trim($_GET['type']) : '';
$searchUser = isset($_GET['user']) ? trim($_GET['user']) : '';
$dateFrom   = isset($_GET['from']) ? trim($_GET['from']) : '';
$dateTo     = isset($_GET['to'])   ? trim($_GET['to']) : '';

// ── Build Query Dynamically ──────────────────────────────────
$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($filterType !== '') {
    $whereClauses[] = "i.incident_type = ?";
    $params[] = $filterType;
    $types .= "s";
}
if ($searchUser !== '') {
    $whereClauses[] = "u.fullname LIKE ?";
    $params[] = "%" . $searchUser . "%";
    $types .= "s";
}
if ($dateFrom !== '') {
    $whereClauses[] = "i.created_at >= ?";
    $params[] = $dateFrom . " 00:00:00";
    $types .= "s";
}
if ($dateTo !== '') {
    $whereClauses[] = "i.created_at <= ?";
    $params[] = $dateTo . " 23:59:59";
    $types .= "s";
}

$whereSQL = implode(" AND ", $whereClauses);

// ── Get Total Matches Count ──────────────────────────────────
$countSQL = "SELECT COUNT(*) as total FROM incidents i JOIN users u ON u.id = i.user_id WHERE $whereSQL";
$countStmt = $db->prepare($countSQL);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalRecords / $limit);

// ── Get Records ──────────────────────────────────────────────
$dataSQL = "SELECT i.*, u.fullname 
            FROM incidents i 
            JOIN users u ON u.id = i.user_id 
            WHERE $whereSQL 
            ORDER BY i.created_at DESC 
            LIMIT ? OFFSET ?";
            
$dataStmt = $db->prepare($dataSQL);

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$result = $dataStmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$dataStmt->close();

echo json_encode([
    'logs'         => $logs,
    'currentPage'  => $page,
    'totalPages'   => $totalPages,
    'totalRecords' => $totalRecords
]);