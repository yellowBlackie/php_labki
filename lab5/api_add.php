<?php

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'error' => 'Дозволено лише POST-запит.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $type = trim($_POST['type'] ?? '');
    $duration = $_POST['duration_min'] ?? '';
    $calories = $_POST['calories_burned'] ?? '';
    $date = $_POST['workout_date'] ?? '';

    if (
        $type === '' ||
        $duration === '' ||
        $calories === '' ||
        $date === ''
    ) {
        http_response_code(400);

        echo json_encode([
            'error' => 'Заповніть усі поля.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO workouts
        (type, duration_min, calories_burned, workout_date)
        VALUES
        (:type, :duration, :calories, :date)'
    );

    $stmt->execute([
        ':type' => $type,
        ':duration' => $duration,
        ':calories' => $calories,
        ':date' => $date
    ]);

    $id = $pdo->lastInsertId();

    http_response_code(201);

    echo json_encode([
        'id' => $id,
        'type' => $type,
        'duration_min' => (int)$duration,
        'calories_burned' => (int)$calories,
        'workout_date' => $date
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'error' => 'Не вдалося додати тренування.'
    ], JSON_UNESCAPED_UNICODE);
}