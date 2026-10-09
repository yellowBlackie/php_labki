<?php

header(
    'Content-Type: application/json; charset=utf-8'
);

ini_set('display_errors', '0');

require_once 'csrf.php';

try {
    require_once 'db.php';

    $method =
        $_SERVER['REQUEST_METHOD'];

    $resource =
        $_GET['resource'] ?? null;

    $id =
        $_GET['id'] ?? null;

    $action =
        $_GET['action'] ?? null;


    if ($resource !== 'workouts') {
        sendError(
            'Невідомий ресурс.',
            404
        );
    }


    // GET: список, один запис або отримання CSRF-токена
    if ($method === 'GET') {

        if ($action === 'csrf') {
            sendSuccess([
                'csrf_token' =>
                    getCsrfToken()
            ], 200);
        }

        if ($action !== null) {
            sendError(
                'Невідома дія.',
                400
            );
        }

        if ($id === null) {
            listWorkouts($pdo);
        }

        getWorkout(
            $pdo,
            $id
        );
    }


    // POST: створення тренування або доменна дія stats
    if ($method === 'POST') {

        if ($action === null) {
            createWorkout($pdo);
        }

        if ($action === 'stats') {
            getWorkoutStats($pdo);
        }

        sendError(
            'Невідома дія.',
            400
        );
    }


    // PUT, DELETE та інші методи не підтримуються
    sendError(
        'Метод не підтримується.',
        405
    );

} catch (Throwable $e) {

    error_log(
        'Lab6 API error: ' .
        $e->getMessage()
    );

    sendError(
        'Внутрішня помилка сервера.',
        500
    );
}


// GET: список усіх тренувань
function listWorkouts(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT id,
                type,
                duration_min,
                calories_burned,
                workout_date
         FROM workouts
         ORDER BY workout_date DESC, id DESC'
    );

    sendSuccess(
        $stmt->fetchAll(),
        200
    );
}


// GET: одне тренування за id
function getWorkout(
    PDO $pdo,
    $id
): void {
    if (
        filter_var(
            $id,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$id <= 0
    ) {
        sendError(
            'Некоректний id.',
            400
        );
    }

    $stmt = $pdo->prepare(
        'SELECT id,
                type,
                duration_min,
                calories_burned,
                workout_date
         FROM workouts
         WHERE id = :id'
    );

    $stmt->execute([
        ':id' => (int)$id
    ]);

    $workout =
        $stmt->fetch();

    if (!$workout) {
        sendError(
            'Тренування не знайдено.',
            404
        );
    }

    sendSuccess(
        $workout,
        200
    );
}


// POST: створення нового тренування
function createWorkout(PDO $pdo): void
{
    $data =
        getRequestData();

    verifyCsrfToken($data);

    $type = trim(
        $data['type'] ?? ''
    );

    $duration =
        $data['duration_min']
        ?? null;

    $calories =
        $data['calories_burned']
        ?? null;

    $date = trim(
        $data['workout_date']
        ?? ''
    );


    // Перевірка обов'язкових полів
    if ($type === '') {
        sendError(
            'Поле type є обов’язковим.',
            400
        );
    }

    if (
        $duration === null ||
        $duration === ''
    ) {
        sendError(
            'Поле duration_min є обов’язковим.',
            400
        );
    }

    if (
        $calories === null ||
        $calories === ''
    ) {
        sendError(
            'Поле calories_burned є обов’язковим.',
            400
        );
    }

    if ($date === '') {
        sendError(
            'Поле workout_date є обов’язковим.',
            400
        );
    }


    if (mb_strlen($type) > 100) {
        sendError(
            'Поле type є занадто довгим.',
            400
        );
    }


    // Тривалість має бути додатним цілим числом
    if (
        filter_var(
            $duration,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$duration <= 0
    ) {
        sendError(
            'duration_min має бути додатним цілим числом.',
            400
        );
    }


    // Калорії мають бути невід'ємним цілим числом
    if (
        filter_var(
            $calories,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$calories < 0
    ) {
        sendError(
            'calories_burned має бути невід’ємним цілим числом.',
            400
        );
    }


    // Перевірка формату дати
    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d')
            !== $date
    ) {
        sendError(
            'workout_date має бути у форматі YYYY-MM-DD.',
            400
        );
    }


    // INSERT тільки через prepared statement
    $stmt = $pdo->prepare(
        'INSERT INTO workouts
         (
            type,
            duration_min,
            calories_burned,
            workout_date
         )
         VALUES
         (
            :type,
            :duration,
            :calories,
            :date
         )'
    );

    $stmt->execute([
        ':type' =>
            $type,

        ':duration' =>
            (int)$duration,

        ':calories' =>
            (int)$calories,

        ':date' =>
            $date
    ]);


    $newId =
        (int)$pdo->lastInsertId();


    $stmt = $pdo->prepare(
        'SELECT id,
                type,
                duration_min,
                calories_burned,
                workout_date
         FROM workouts
         WHERE id = :id'
    );

    $stmt->execute([
        ':id' => $newId
    ]);

    $workout =
        $stmt->fetch();

    sendSuccess(
        $workout,
        201
    );
}


// POST action=stats:
// повертає сумарні калорії,
// опціонально з фільтром за типом
function getWorkoutStats(PDO $pdo): void
{
    $data =
        getRequestData();

    verifyCsrfToken($data);

    $type = trim(
        $data['type']
        ?? ''
    );

    if ($type !== '') {

        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(
                    SUM(calories_burned),
                    0
                ) AS total_calories
             FROM workouts
             WHERE type LIKE :type'
        );

        $stmt->execute([
            ':type' =>
                '%' . $type . '%'
        ]);

    } else {

        $stmt = $pdo->query(
            'SELECT
                COALESCE(
                    SUM(calories_burned),
                    0
                ) AS total_calories
             FROM workouts'
        );
    }

    $result =
        $stmt->fetch();

    sendSuccess([
        'filter' =>
            $type !== ''
                ? $type
                : null,

        'total_calories' =>
            (int)$result['total_calories']
    ], 200);
}


// Читає POST-дані.
// Підтримує form-urlencoded і JSON.
function getRequestData(): array
{
    $contentType =
        $_SERVER['CONTENT_TYPE']
        ?? '';

    if (
        str_contains(
            $contentType,
            'application/json'
        )
    ) {
        $raw =
            file_get_contents(
                'php://input'
            );

        $data =
            json_decode(
                $raw,
                true
            );

        if (!is_array($data)) {
            sendError(
                'Некоректний JSON.',
                400
            );
        }

        return $data;
    }

    return $_POST;
}


// Успішна JSON-відповідь
function sendSuccess(
    $data,
    int $statusCode = 200
): void {
    http_response_code(
        $statusCode
    );

    echo json_encode([
        'success' =>
            true,

        'data' =>
            $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// JSON-відповідь з помилкою
function sendError(
    string $message,
    int $statusCode
): void {
    http_response_code(
        $statusCode
    );

    echo json_encode([
        'success' =>
            false,

        'error' =>
            $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
