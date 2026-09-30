<?php
require_once 'db.php';

$stmt = $pdo->query('SELECT * FROM workouts ORDER BY workout_date DESC');
$workouts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Фітнес-трекер (Практична 4)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f4f4f4; }
        .form-container { background: #e9ecef; padding: 15px; border-radius: 5px; }
        .form-group { margin-bottom: 10px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        .btn-edit { color: blue; text-decoration: none; margin-right: 10px; }
        .btn-delete { color: red; text-decoration: none; }
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
                    <td><?= $w['id'] ?></td>
                    <td><?= htmlspecialchars($w['type']) ?></td>
                    <td><?= $w['duration_min'] ?></td>
                    <td><?= $w['calories_burned'] ?></td>
                    <td><?= $w['workout_date'] ?></td>
                    <td>
                        <a href="edit.php?id=<?= $w['id'] ?>" class="btn-edit">Редагувати</a>
                        <!-- JS-підтвердження перед видаленням -->
                        <a href="delete.php?id=<?= $w['id'] ?>" class="btn-delete" onclick="return confirm('Ви впевнені, що хочете видалити цей запис?')">Видалити</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($workouts)): ?>
                <tr><td colspan="6" style="text-align: center;">Немає записів.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="form-container">
        <h3>Додати нове тренування</h3>
        <form action="add.php" method="POST">
            <div class="form-group">
                <label>Тип тренування:</label>
                <input type="text" name="type" required>
            </div>
            <div class="form-group">
                <label>Тривалість (хв):</label>
                <input type="number" name="duration_min" required>
            </div>
            <div class="form-group">
                <label>Калорії:</label>
                <input type="number" name="calories_burned" required>
            </div>
            <div class="form-group">
                <label>Дата:</label>
                <input type="date" name="workout_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit">Додати</button>
        </form>
    </div>

</body>
</html>