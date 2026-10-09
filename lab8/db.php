<?php

$host = 'localhost';
$port = '3307';
$db = 'practicum4';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn =
    "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE =>
        PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE =>
        PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        $options
    );
} catch (PDOException $e) {
    // Технічні деталі залишаються тільки в логах сервера
    error_log(
        'Lab8 database connection error: ' .
        $e->getMessage()
    );

    throw new RuntimeException(
        'Database connection failed.'
    );
}
