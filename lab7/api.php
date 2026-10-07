<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'db.php';

    $method = $_SERVER['REQUEST_METHOD'];
    $resource = $_GET['resource'] ?? null;
    $id = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? null;

    // У цій роботі підтримуємо тільки ресурс workouts
    if ($resource !== 'workouts') {
        sendError('Невідомий ресурс.', 404);
    }

    // GET: список тренувань або одне тренування за id
    if ($method === 'GET') {
        if ($id === null) {
            listWorkouts($pdo);
        } else {
            getWorkout($pdo, $id);
        }
    }

    // POST: створення тренування або отримання статистики
    if ($method === 'POST') {
        if ($action === null) {
            createWorkout($pdo);
        }

        if ($action === 'stats') {
            getWorkoutStats($pdo);
        }

        sendError('Невідома дія.', 400);
    }

    // PUT, DELETE та інші методи не підтримуються
    sendError('Метод не підтримується.', 405);

} catch (PDOException $e) {
    sendError('Помилка роботи з базою даних.', 500);
}


// Повертає шлях до файлу кешу
function getTotalsCacheFile(): string
{
    return sys_get_temp_dir()
        . DIRECTORY_SEPARATOR
        . 'lab7_workout_totals.json';
}


// Отримує сумарні калорії для кожного типу тренування
// Якщо кеш ще актуальний, SQL-запит не виконується
function getCachedTotalsByType(PDO $pdo, int $ttlSeconds = 60): array
{
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

    // Якщо кешу немає або TTL закінчився,
    // виконуємо один агрегатний запит замість N+1
    $stmt = $pdo->query(
        'SELECT type, SUM(calories_burned) AS total_calories
         FROM workouts
         GROUP BY type'
    );

    $rows = $stmt->fetchAll();

    $totals = [];

    foreach ($rows as $row) {
        $totals[$row['type']] =
            (int)$row['total_calories'];
    }

    // Зберігаємо результат у файловий кеш
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


// Видаляє кеш після зміни даних
function clearWorkoutCache(): void
{
    $cacheFile = getTotalsCacheFile();

    if (is_file($cacheFile)) {
        unlink($cacheFile);
    }
}


// GET: повертає список усіх тренувань
function listWorkouts(PDO $pdo): void
{
    $start = microtime(true);

    $sqlQueries = 0;

    // Перший SQL-запит: отримуємо список тренувань
    $stmt = $pdo->query(
        'SELECT id, type, duration_min, calories_burned, workout_date
         FROM workouts
         ORDER BY workout_date DESC, id DESC'
    );

    $sqlQueries++;

    $workouts = $stmt->fetchAll();

    // Суми калорій беремо з кешу або одним GROUP BY
    $totalsResult = getCachedTotalsByType($pdo, 60);

    $sqlQueries += $totalsResult['sql_queries'];

    $totals = $totalsResult['data'];

    // Додаємо сумарні калорії відповідного типу до кожного запису
    // Тут додаткові SQL-запити вже не виконуються
    foreach ($workouts as &$workout) {
        $workout['type_total_calories'] =
            (int)($totals[$workout['type']] ?? 0);
    }

    unset($workout);

    // Вимірюємо час виконання
    $elapsed =
        (microtime(true) - $start) * 1000;

    // Вимірюємо пікове використання пам'яті
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


// GET: повертає одне тренування за id
function getWorkout(PDO $pdo, $id): void
{
    if (
        !filter_var($id, FILTER_VALIDATE_INT) ||
        (int)$id <= 0
    ) {
        sendError('Некоректний id.', 400);
    }

    $stmt = $pdo->prepare(
        'SELECT id, type, duration_min, calories_burned, workout_date
         FROM workouts
         WHERE id = :id'
    );

    $stmt->execute([
        ':id' => (int)$id
    ]);

    $workout = $stmt->fetch();

    if (!$workout) {
        sendError('Тренування не знайдено.', 404);
    }

    sendSuccess($workout, 200);
}


// POST: створює нове тренування
function createWorkout(PDO $pdo): void
{
    $data = getRequestData();

    $type = trim($data['type'] ?? '');
    $duration = $data['duration_min'] ?? null;
    $calories = $data['calories_burned'] ?? null;
    $date = $data['workout_date'] ?? '';

    // Перевіряємо обов'язкові поля
    if ($type === '') {
        sendError(
            'Поле type є обов’язковим.',
            400
        );
    }

    if ($duration === null || $duration === '') {
        sendError(
            'Поле duration_min є обов’язковим.',
            400
        );
    }

    if ($calories === null || $calories === '') {
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

    // Тривалість повинна бути додатним цілим числом
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

    // Перевіряємо формат дати
    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $date
    ) {
        sendError(
            'workout_date має бути у форматі YYYY-MM-DD.',
            400
        );
    }

    // Додаємо тренування через prepared statement
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

    // Після зміни даних старий кеш більше не актуальний
    clearWorkoutCache();

    $newId = (int)$pdo->lastInsertId();

    // Отримуємо створений запис
    $stmt = $pdo->prepare(
        'SELECT id, type, duration_min, calories_burned, workout_date
         FROM workouts
         WHERE id = :id'
    );

    $stmt->execute([
        ':id' => $newId
    ]);

    $workout = $stmt->fetch();

    sendSuccess($workout, 201);
}


// POST action=stats:
// повертає сумарні калорії за поточним фільтром type
function getWorkoutStats(PDO $pdo): void
{
    $data = getRequestData();

    $type = trim(
        $data['type']
        ?? $_GET['type']
        ?? ''
    );

    // Використовуємо той самий кеш агрегованих значень
    $totalsResult =
        getCachedTotalsByType($pdo, 60);

    $totals =
        $totalsResult['data'];

    $totalCalories = 0;

    if ($type === '') {
        // Без фільтра рахуємо загальну суму
        foreach ($totals as $value) {
            $totalCalories += (int)$value;
        }
    } else {
        // З фільтром сумуємо тільки відповідні типи
        foreach ($totals as $workoutType => $value) {

            if (function_exists('mb_stripos')) {
                $matches =
                    mb_stripos(
                        $workoutType,
                        $type
                    ) !== false;
            } else {
                $matches =
                    stripos(
                        $workoutType,
                        $type
                    ) !== false;
            }

            if ($matches) {
                $totalCalories +=
                    (int)$value;
            }
        }
    }

    sendSuccess([
        'filter' =>
            $type !== '' ? $type : null,

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
        $_SERVER['CONTENT_TYPE'] ?? '';

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


// Повертає успішну JSON-відповідь
function sendSuccess(
    $data,
    int $statusCode = 200
): void
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// Повертає JSON-відповідь з помилкою
function sendError(
    string $message,
    int $statusCode
): void
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}