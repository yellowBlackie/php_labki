<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'error' => 'Дозволено лише GET-запит.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    require_once 'db.php';

    $stmt = $pdo->query(
        'SELECT COALESCE(SUM(calories_burned), 0) AS total_calories
         FROM workouts'
    );

    $result = $stmt->fetch();

    echo json_encode([
        'total_calories' =>
            (int)$result['total_calories']
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log(
        'Lab5 api_stats error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'error' => 'Не вдалося отримати статистику.'
    ], JSON_UNESCAPED_UNICODE);
}
