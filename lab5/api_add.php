<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'error' => 'Дозволено лише POST-запит.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    require_once 'db.php';

    verifyCsrfToken(
        $_POST['csrf_token'] ?? null
    );

    $type = trim(
        $_POST['type'] ?? ''
    );

    $duration =
        $_POST['duration_min'] ?? '';

    $calories =
        $_POST['calories_burned'] ?? '';

    $date = trim(
        $_POST['workout_date'] ?? ''
    );

    // Перевіряємо обов'язкові поля на сервері
    if ($type === '') {
        http_response_code(400);

        echo json_encode([
            'error' => 'Поле type є обов’язковим.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($duration === '') {
        http_response_code(400);

        echo json_encode([
            'error' => 'Поле duration_min є обов’язковим.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($calories === '') {
        http_response_code(400);

        echo json_encode([
            'error' => 'Поле calories_burned є обов’язковим.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($date === '') {
        http_response_code(400);

        echo json_encode([
            'error' => 'Поле workout_date є обов’язковим.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (mb_strlen($type) > 100) {
        http_response_code(400);

        echo json_encode([
            'error' => 'Поле type є занадто довгим.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Тривалість має бути додатним цілим числом
    if (
        filter_var(
            $duration,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$duration <= 0
    ) {
        http_response_code(400);

        echo json_encode([
            'error' => 'duration_min має бути додатним цілим числом.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Калорії мають бути невід'ємним цілим числом
    if (
        filter_var(
            $calories,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$calories < 0
    ) {
        http_response_code(400);

        echo json_encode([
            'error' => 'calories_burned має бути невід’ємним цілим числом.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Перевіряємо формат дати
    $dateObject = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $date
    ) {
        http_response_code(400);

        echo json_encode([
            'error' => 'workout_date має бути у форматі YYYY-MM-DD.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Дані користувача передаються тільки через prepared statement
    $stmt = $pdo->prepare(
        'INSERT INTO workouts
         (type, duration_min, calories_burned, workout_date)
         VALUES
         (:type, :duration, :calories, :date)'
    );

    $stmt->execute([
        ':type' => $type,
        ':duration' => (int)$duration,
        ':calories' => (int)$calories,
        ':date' => $date
    ]);

    $id = (int)$pdo->lastInsertId();

    http_response_code(201);

    echo json_encode([
        'id' => $id,
        'type' => $type,
        'duration_min' => (int)$duration,
        'calories_burned' => (int)$calories,
        'workout_date' => $date
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log(
        'Lab5 api_add error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'error' => 'Не вдалося додати тренування.'
    ], JSON_UNESCAPED_UNICODE);
}
