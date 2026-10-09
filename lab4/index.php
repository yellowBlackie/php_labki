<?php

require_once 'db.php';
require_once 'csrf.php';

$stmt = $pdo->query(
    'SELECT id, type, duration_min, calories_burned, workout_date
     FROM workouts
     ORDER BY workout_date DESC, id DESC'
);

$workouts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Фітнес-трекер (Практична 4)</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 0 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        .form-container {
            background: #e9ecef;
            padding: 18px;
            border-radius: 6px;
        }

        .form-group {
            margin-bottom: 12px;
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
            padding: 9px 14px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-add {
            background: #007bff;
            color: white;
        }

        .btn-edit {
            color: blue;
            text-decoration: none;
            margin-right: 10px;
        }

        .btn-delete {
            background: transparent;
            color: red;
            padding: 0;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <h2>Список тренувань</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Тип</th>
                <th>Тривалість (хв)</th>
                <th>Калорії</th>
                <th>Дата</th>
                <th>Дії</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($workouts as $w): ?>
                <tr>
                    <td><?= (int)$w['id'] ?></td>
                    <td><?= htmlspecialchars($w['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$w['duration_min'] ?></td>
                    <td><?= (int)$w['calories_burned'] ?></td>
                    <td><?= htmlspecialchars($w['workout_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a
                            href="edit.php?id=<?= (int)$w['id'] ?>"
                            class="btn-edit"
                        >
                            Редагувати
                        </a>

                        <form
                            action="delete.php"
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Ви впевнені, що хочете видалити цей запис?');"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$w['id'] ?>"
                            >

                            <button
                                type="submit"
                                class="btn-delete"
                            >
                                Видалити
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($workouts)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">
                        Немає записів.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="form-container">
        <h3>Додати нове тренування</h3>

        <form action="add.php" method="POST">
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
                    required
                >
            </div>

            <div class="form-group">
                <label for="workout_date">Дата:</label>
                <input
                    type="date"
                    id="workout_date"
                    name="workout_date"
                    value="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <button type="submit" class="btn-add">
                Додати
            </button>
        </form>
    </div>

</body>
</html>
