<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

try {
    require_once 'db.php';

    $start =
        microtime(true);

    $sqlQueries = 0;

    // Варіант ДО оптимізації:
    // спочатку отримуємо весь список тренувань.
    $stmt = $pdo->query(
        'SELECT id,
                type,
                duration_min,
                calories_burned,
                workout_date
         FROM workouts
         ORDER BY workout_date DESC, id DESC'
    );

    $sqlQueries++;

    $workouts =
        $stmt->fetchAll();

    // N+1:
    // для кожного тренування виконується ще один SQL-запит.
    foreach ($workouts as &$workout) {

        $stmt = $pdo->prepare(
            'SELECT
                COALESCE(
                    SUM(calories_burned),
                    0
                )
             FROM workouts
             WHERE type = :type'
        );

        $stmt->execute([
            ':type' =>
                $workout['type']
        ]);

        $sqlQueries++;

        $workout['type_total_calories'] =
            (int)$stmt->fetchColumn();
    }

    unset($workout);

    $elapsed =
        (microtime(true) - $start)
        * 1000;

    $peakMemory =
        memory_get_peak_usage(true)
        / 1024
        / 1024;

    echo json_encode([
        'success' => true,

        'data' => [
            'workouts' =>
                $workouts,

            'profiling' => [
                'execution_time_ms' =>
                    round($elapsed, 3),

                'peak_memory_mb' =>
                    round($peakMemory, 3),

                'sql_queries' =>
                    $sqlQueries,

                'optimization' =>
                    'before_n_plus_1'
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log(
        'Lab7 before N+1 error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' =>
            'Внутрішня помилка сервера.'
    ], JSON_UNESCAPED_UNICODE);
}
