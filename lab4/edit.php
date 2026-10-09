<?php

require_once 'db.php';
require_once 'csrf.php';

$id = $_GET['id'] ?? null;

if (
    filter_var($id, FILTER_VALIDATE_INT) === false ||
    (int)$id <= 0
) {
    http_response_code(400);
    exit('Некоректний id.');
}

$id = (int)$id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $type = trim($_POST['type'] ?? '');
    $duration = $_POST['duration_min'] ?? null;
    $calories = $_POST['calories_burned'] ?? null;
    $date = trim($_POST['workout_date'] ?? '');

    if ($type === '') {
        http_response_code(400);
        exit('Поле type є обов’язковим.');
    }

    if ($duration === null || $duration === '') {
        http_response_code(400);
        exit('Поле duration_min є обов’язковим.');
    }

    if ($calories === null || $calories === '') {
        http_response_code(400);
        exit('Поле calories_burned є обов’язковим.');
    }

    if ($date === '') {
        http_response_code(400);
        exit('Поле workout_date є обов’язковим.');
    }

    if (strlen($type) > 100) {
        http_response_code(400);
        exit('Поле type є занадто довгим.');
    }

    if (
        filter_var($duration, FILTER_VALIDATE_INT) === false ||
        (int)$duration <= 0
    ) {
        http_response_code(400);
        exit('Тривалість має бути додатним цілим числом.');
    }

    if (
        filter_var($calories, FILTER_VALIDATE_INT) === false ||
        (int)$calories < 0
    ) {
        http_response_code(400);
        exit('Калорії мають бути невід’ємним цілим числом.');
    }

    $dateObject = DateTime::createFromFormat('Y-m-d', $date);

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $date
    ) {
        http_response_code(400);
        exit('Дата має бути у форматі YYYY-MM-DD.');
    }

    $stmt = $pdo->prepare(
        'UPDATE workouts
         SET type = :type,
             duration_min = :duration,
             calories_burned = :calories,
             workout_date = :date
         WHERE id = :id'
    );

    $stmt->execute([
        ':type' => $type,
        ':duration' => (int)$duration,
        ':calories' => (int)$calories,
        ':date' => $date,
        ':id' => $id,
    ]);

    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, type, duration_min, calories_burned, workout_date
     FROM workouts
     WHERE id = :id'
);

$stmt->execute([
    ':id' => $id,
]);

$workout = $stmt->fetch();

if (!$workout) {
    http_response_code(404);
    exit('Тренування не знайдено.');
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагувати тренування</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 450px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 14px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <h2>Редагувати тренування</h2>

    <form action="edit.php?id=<?= $id ?>" method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"
        >

        <div class="form-group">
            <label for="type">Тип тренування:</label>
            <input
                type="text"
                id="type"
                name="type"
                maxlength="100"
                value="<?= htmlspecialchars($workout['type'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="duration_min">Тривалість (хв):</label>
            <input
                type="number"
                id="duration_min"
                name="duration_min"
                min="1"
                value="<?= (int)$workout['duration_min'] ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="calories_burned">Калорії:</label>
            <input
                type="number"
                id="calories_burned"
                name="calories_burned"
                min="0"
                value="<?= (int)$workout['calories_burned'] ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="workout_date">Дата:</label>
            <input
                type="date"
                id="workout_date"
                name="workout_date"
                value="<?= htmlspecialchars($workout['workout_date'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <button type="submit">
            Зберегти зміни
        </button>

        <a href="index.php" style="margin-left:10px;">
            Скасувати
        </a>
    </form>

</body>
</html>
