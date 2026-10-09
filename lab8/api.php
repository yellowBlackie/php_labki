<?php

session_start();

if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

header('Content-Type: application/json; charset=utf-8');

// Не показуємо користувачу сирі PHP-помилки.
// Деталі помилок записуються в лог.
ini_set('display_errors', '0');

try {
    require_once 'db.php';

    $method = $_SERVER['REQUEST_METHOD'];
    $resource = $_GET['resource'] ?? null;
    $id = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? null;

    if ($resource !== 'workouts') {
        sendError('Невідомий ресурс.', 404);
    }

    if ($method === 'GET') {

    // Доменні дії не дозволяємо запускати через GET
    if ($action !== null) {
        sendError(
            'Дія доступна лише через POST.',
            405
        );
    }

    if ($id === null) {
        listWorkouts($pdo);
    } else {
        getWorkout($pdo, $id);
    }
}

    // POST: додавання, статистика або редагування тренування
    if ($method === 'POST') {
        if ($action === null) {
            createWorkout($pdo);
        }

        if ($action === 'stats') {
            getWorkoutStats($pdo);
        }

        if ($action === 'update') {
            updateWorkout($pdo);
        }

        sendError('Невідома дія.', 400);
    }

    // Дії, які змінюють дані, через GET не виконуються.
    // PUT, DELETE та інші методи також не підтримуються.
    sendError('Метод не підтримується.', 405);

} catch (Throwable $e) {
    // Детальна помилка залишається тільки в логах сервера
    error_log(
        'Lab8 error: ' .
        $e->getMessage()
    );

    sendError(
        'Внутрішня помилка сервера.',
        500
    );
}


// Повертає шлях до файлового кешу
function getTotalsCacheFile(): string
{
    return sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'lab8_workout_totals.json';
}


// Отримує сумарні калорії за типами.
// Якщо кеш актуальний, додатковий SQL-запит не виконується.
function getCachedTotalsByType(
    PDO $pdo,
    int $ttlSeconds = 60
): array {
    $cacheFile = getTotalsCacheFile();

    if (
        is_file($cacheFile) &&
        (time() - filemtime($cacheFile)) < $ttlSeconds
    ) {
        $cached = json_decode(
            file_get_contents($cacheFile),
            true
        );

        if (is_array($cached)) {
            return [
                'data' => $cached,
                'from_cache' => true,
                'sql_queries' => 0
            ];
        }
    }

    $stmt = $pdo->query(
        'SELECT type,
                SUM(calories_burned) AS total_calories
         FROM workouts
         GROUP BY type'
    );

    $rows = $stmt->fetchAll();

    $totals = [];

    foreach ($rows as $row) {
        $totals[$row['type']] =
            (int)$row['total_calories'];
    }

    file_put_contents(
        $cacheFile,
        json_encode(
            $totals,
            JSON_UNESCAPED_UNICODE
        )
    );

    return [
        'data' => $totals,
        'from_cache' => false,
        'sql_queries' => 1
    ];
}


// Очищає кеш після зміни даних
function clearWorkoutCache(): void
{
    $cacheFile = getTotalsCacheFile();

    if (is_file($cacheFile)) {
        unlink($cacheFile);
    }
}


// Перевіряє CSRF-токен для POST-запитів
function verifyCsrfToken(array $data): void
{
    $sessionToken =
        $_SESSION['csrf_token'] ?? '';

    $requestToken =
        $data['csrf_token'] ?? '';

    if (
        $sessionToken === '' ||
        $requestToken === '' ||
        !hash_equals(
            $sessionToken,
            $requestToken
        )
    ) {
        sendError(
            'Недійсний CSRF-токен.',
            403
        );
    }
}


// GET: список тренувань.
// Параметр type передається в prepared statement,
// тому не може змінити структуру SQL-запиту.
function listWorkouts(PDO $pdo): void
{
    $start = microtime(true);

    $sqlQueries = 0;

    $type = trim(
        $_GET['type'] ?? ''
    );

    if ($type !== '') {
        $stmt = $pdo->prepare(
            'SELECT id,
                    type,
                    duration_min,
                    calories_burned,
                    workout_date
             FROM workouts
             WHERE type LIKE :type
             ORDER BY workout_date DESC, id DESC'
        );

        $stmt->execute([
            ':type' => '%' . $type . '%'
        ]);
    } else {
        $stmt = $pdo->query(
            'SELECT id,
                    type,
                    duration_min,
                    calories_burned,
                    workout_date
             FROM workouts
             ORDER BY workout_date DESC, id DESC'
        );
    }

    $sqlQueries++;

    $workouts =
        $stmt->fetchAll();

    $totalsResult =
        getCachedTotalsByType(
            $pdo,
            60
        );

    $sqlQueries +=
        $totalsResult['sql_queries'];

    $totals =
        $totalsResult['data'];

    foreach ($workouts as &$workout) {
        $workout['type_total_calories'] =
            (int)(
                $totals[$workout['type']]
                ?? 0
            );
    }

    unset($workout);

    $elapsed =
        (microtime(true) - $start)
        * 1000;

    $peakMemory =
        memory_get_peak_usage(true)
        / 1024
        / 1024;

    sendSuccess([
        'workouts' => $workouts,

        'profiling' => [
            'execution_time_ms' =>
                round($elapsed, 3),

            'peak_memory_mb' =>
                round($peakMemory, 3),

            'sql_queries' =>
                $sqlQueries,

            'totals_from_cache' =>
                $totalsResult['from_cache']
        ]
    ], 200);
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

    // CSRF перевіряється на сервері
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

    // Серверна перевірка обов'язкових полів
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

    // Обмежуємо довжину типу тренування
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

    // Калорії не можуть бути від'ємними
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

    // Перевіряємо правильність дати
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

    // INSERT виконується тільки через prepared statement
        $stmt = $pdo->prepare(
        'INSERT INTO workouts
        (
            owner_id,
            type,
            duration_min,
            calories_burned,
            workout_date
        )
        VALUES
        (
            :owner_id,
            :type,
            :duration,
            :calories,
            :date
        )'
    );

    $stmt->execute([
        ':owner_id' =>
            (int)$_SESSION['user_id'],
        ':type' =>
            $type,
        ':duration' =>
            (int)$duration,
        ':calories' =>
            (int)$calories,
        ':date' =>
            $date
    ]);

    // Після зміни даних очищаємо кеш
    clearWorkoutCache();

    $newId =
        (int)$pdo->lastInsertId();

    // Отримуємо щойно створений запис
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


// POST action=update:
// редагує тільки тренування поточного користувача
function updateWorkout(PDO $pdo): void
{
    $data = getRequestData();

    // Редагування захищене CSRF-токеном
    verifyCsrfToken($data);

    $id =
        $data['id']
        ?? null;

    $type = trim(
        $data['type']
        ?? ''
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

    // Перевірка id
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

    // Перевірка type
    if ($type === '') {
        sendError(
            'Поле type є обов’язковим.',
            400
        );
    }

    if (mb_strlen($type) > 100) {
        sendError(
            'Поле type є занадто довгим.',
            400
        );
    }

    // Перевірка тривалості
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

    // Перевірка калорій
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

    // Перевірка дати
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

    $ownerId =
        (int)$_SESSION['user_id'];

    // Спочатку отримуємо власника тренування
    $stmt = $pdo->prepare(
        'SELECT id, owner_id
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

    // Забороняємо редагування чужого тренування
    if (
        (int)$workout['owner_id']
        !== $ownerId
    ) {
        sendError(
            'Немає прав на редагування цього тренування.',
            403
        );
    }

    // UPDATE також обмежений owner_id
    $stmt = $pdo->prepare(
        'UPDATE workouts
         SET type = :type,
             duration_min = :duration,
             calories_burned = :calories,
             workout_date = :date
         WHERE id = :id
           AND owner_id = :owner_id'
    );

    $stmt->execute([
        ':type' => $type,
        ':duration' => (int)$duration,
        ':calories' => (int)$calories,
        ':date' => $date,
        ':id' => (int)$id,
        ':owner_id' => $ownerId
    ]);

    clearWorkoutCache();

    sendSuccess([
        'id' => (int)$id,
        'message' =>
            'Тренування успішно оновлено.'
    ], 200);
}

// POST action=stats:
// повертає сумарні калорії за поточним фільтром
function getWorkoutStats(
    PDO $pdo
): void {
    $data =
        getRequestData();

    // Доменна POST-дія також захищена CSRF
    verifyCsrfToken($data);

    $type = trim(
        $data['type']
        ?? $_GET['type']
        ?? ''
    );

    $totalsResult =
        getCachedTotalsByType(
            $pdo,
            60
        );

    $totals =
        $totalsResult['data'];

    $totalCalories = 0;

    if ($type === '') {
        foreach (
            $totals as $value
        ) {
            $totalCalories +=
                (int)$value;
        }
    } else {
        foreach (
            $totals
            as $workoutType => $value
        ) {
            if (
                mb_stripos(
                    $workoutType,
                    $type
                ) !== false
            ) {
                $totalCalories +=
                    (int)$value;
            }
        }
    }

    sendSuccess([
        'filter' =>
            $type !== ''
                ? $type
                : null,

        'total_calories' =>
            $totalCalories,

        'from_cache' =>
            $totalsResult['from_cache']
    ], 200);
}


// Читає POST-дані зі звичайної форми або JSON
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
        'success' => true,
        'data' => $data
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
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}