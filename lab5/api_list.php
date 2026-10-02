<?php

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $search = trim($_GET['q'] ?? '');

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

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'error' => 'Не вдалося отримати список тренувань.'
    ], JSON_UNESCAPED_UNICODE);
}