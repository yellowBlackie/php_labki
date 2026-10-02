<?php

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query(
        'SELECT COALESCE(SUM(calories_burned), 0) AS total_calories
         FROM workouts'
    );

    $result = $stmt->fetch();

    echo json_encode([
        'total_calories' => (int)$result['total_calories']
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'error' => 'Не вдалося отримати статистику.'
    ], JSON_UNESCAPED_UNICODE);
}