<?php
// крок 7 (увімкніть error_reporting(E_ALL) та display_errors=1 на час розробки)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// крок 2 отголосили масив даних свого домену фітнес трекер
$workouts = [
    ['type' => 'Біг', 'durationMin' => 50, 'caloriesBurned' => 450, 'date' => '2023-10-01'],
    ['type' => 'Йога', 'durationMin' => 30, 'caloriesBurned' => 150, 'date' => '2023-10-02'],
    ['type' => 'Силове тренування', 'durationMin' => 60, 'caloriesBurned' => 500, 'date' => '2023-10-03'],
    ['type' => 'Розтяжка', 'durationMin' => 15, 'caloriesBurned' => 50, 'date' => '2023-10-04'],
    ['type' => 'Плавання', 'durationMin' => 45, 'caloriesBurned' => 400, 'date' => '2023-10-05'],
];

// крок 3 функція форматування
function formatWorkout(array $workout): string {
    return "<strong>{$workout['type']}</strong> ({$workout['date']}) — Тривалість: {$workout['durationMin']} хв.";
}

// крок 6 обчислення агрегатного показника (сумарна кількість спалених калорій)
$totalCalories = 0;
foreach ($workouts as $workout) {
    $totalCalories += $workout['caloriesBurned'];
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практична робота 1 - Фітнес-трекер</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        table { border-collapse: collapse; width: 100%; max-width: 800px; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .summary { padding: 15px; background-color: #e9f5ff; border-left: 4px solid #007bff; display: inline-block;}
        .label-long { color: green; font-weight: bold; }
        .label-short { color: orange; font-weight: bold; }
    </style>
</head>
<body>

    <h2>Список тренувань</h2>

    <table>
        <thead>
            <tr>
                <th>Опис тренування</th>
                <th>Калорії</th>
                <th>Категорія (Мітка)</th>
            </tr>
        </thead>
        <tbody>
            <!-- крок 5 виведення даних через цикл -->
            <?php foreach ($workouts as $workout): ?>
                <?php 
                    // крок 4 умовна логіка для мітки
                    $isLong = $workout['durationMin'] >= 45;
                    $labelClass = $isLong ? 'label-long' : 'label-short';
                    $labelText = $isLong ? 'Довге тренування' : 'Коротке';
                ?>
                <tr>
                    <td><?= formatWorkout($workout) ?></td>
                    <td><?= $workout['caloriesBurned'] ?> ккал</td>
                    <td class="<?= $labelClass ?>"><?= $labelText ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- вивід агрегатного показника -->
    <div class="summary">
        <strong>Загальна статистика:</strong> Сумарна кількість спалених калорій за всі тренування становить 
        <strong><?= $totalCalories ?> ккал</strong>.
    </div>

</body>
</html>