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

    $search = trim(
        $_GET['q'] ?? ''
    );

    // Для пошуку використовується prepared statement
    if ($search !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, type, duration_min, calories_burned, workout_date
             FROM workouts
             WHERE type LIKE :search
             ORDER BY workout_date DESC, id DESC'
        );

        $stmt->execute([
            ':search' => '%' . $search . '%'
        ]);
    } else {
        $stmt = $pdo->query(
            'SELECT id, type, duration_min, calories_burned, workout_date
             FROM workouts
             ORDER BY workout_date DESC, id DESC'
        );
    }

    $workouts = $stmt->fetchAll();

    echo json_encode(
        $workouts,
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {
    error_log(
        'Lab5 api_list error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'error' => 'Не вдалося отримати список тренувань.'
    ], JSON_UNESCAPED_UNICODE);
}
