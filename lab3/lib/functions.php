<?php
function formatDuration(int $minutes): string {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return "{$hours} год {$mins} хв";
    }
    return "{$mins} хв";
}

// Функція розрахунку темпу 
function calcPace(int $durationMin, float $distanceKm): string {
    if ($distanceKm <= 0) return "0:00 хв/км";
    
    $paceDecimal = $durationMin / $distanceKm;
    $paceMinutes = floor($paceDecimal);
    $paceSeconds = round(($paceDecimal - $paceMinutes) * 60);
    
    // Форматуємо секунди, щоб завжди було 2 цифри 
    return sprintf("%d:%02d хв/км", $paceMinutes, $paceSeconds);
}