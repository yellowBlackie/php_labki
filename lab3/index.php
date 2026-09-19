<?php
// Увімкнення відображення помилок
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'classes/Workout.php';
require_once 'classes/CardioWorkout.php';
require_once 'classes/TrainingLog.php';
require_once 'lib/functions.php';

$log = new TrainingLog();
$log->addWorkout(new Workout('Силове тренування', 60, 450, '2023-11-01'));
$log->addWorkout(new Workout('Розтяжка', 20, 100, '2023-11-02'));
$log->addWorkout(new CardioWorkout('Біг', 45, 500, '2023-11-03', 7.5, 155));
$log->addWorkout(new CardioWorkout('Велосипед', 90, 700, '2023-11-04', 25.0, 135));
$allWorkouts = $log->getAllWorkouts();
$totalCalories = $log->totalCalories();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практична робота 3 - Фітнес-трекер (ООП)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; line-height: 1.6; }
        .workout-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .workout-card.cardio { background-color: #f0fdf4; border-color: #bbf7d0; }
        .summary { padding: 15px; background-color: #eff6ff; border-left: 4px solid #3b82f6; margin-top: 20px; }
        .pace-badge { display: inline-block; background: #e2e8f0; padding: 2px 8px; border-radius: 12px; font-size: 0.9em; margin-left: 10px;}
    </style>
</head>
<body>

    <h2>Журнал тренувань</h2>

    <?php foreach ($allWorkouts as $workout): ?>
        <?php 
            $isCardio = $workout instanceof CardioWorkout;
            $cardClass = $isCardio ? 'workout-card cardio' : 'workout-card';
        ?>
        <div class="<?= $cardClass ?>">
        <p><?= $workout->getInfo() ?></p>
            
            <small>
                Форматований час: <?= formatDuration($workout->getDurationMin()) ?> 
                
                <?php if ($isCardio): ?>
                    <span class="pace-badge">
                        Темп: <?= calcPace($workout->getDurationMin(), $workout->getDistanceKm()) ?>
                    </span>
                <?php endif; ?>
            </small>
        </div>
    <?php endforeach; ?>

    <div class="summary">
        <strong>Загальна статистика:</strong><br>
        Всього тренувань: <?= count($allWorkouts) ?><br>
        Сумарно спалено калорій: <strong><?= $totalCalories ?> ккал</strong>
    </div>

</body>
</html>