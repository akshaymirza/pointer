<?php
// db.php - Database Connection Configuration (MySQL / MariaDB via PDO)

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'dynamic_parser';
$db_user = 'root';
$db_pass = '';

function getDBConnection() {
    global $db_host, $db_port, $db_name, $db_user, $db_pass;
    
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Return null or handle error if MySQL is not active
        return null;
    }
}
