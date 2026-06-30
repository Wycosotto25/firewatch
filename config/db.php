<?php
// ============================================================
//  config/db.php  –  Database connection (MySQLi)
// ============================================================

// Automatically use Railway environment variables if they exist, otherwise use localhost defaults
define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'firewatch_db1');
define('DB_PORT', getenv('MYSQLPORT')     ? (int)getenv('MYSQLPORT') : 3306);

function getDB(): mysqli  
{
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        http_response_code(503);
        die(json_encode([
            'error' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
?>