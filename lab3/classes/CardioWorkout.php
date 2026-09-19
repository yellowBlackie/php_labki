<?php
require_once 'Workout.php'; 

class CardioWorkout extends Workout {
    private float $distanceKm;
    private int $avgHeartRate;

    public function __construct(string $type, int $durationMin, int $caloriesBurned, string $date, float $distanceKm, int $avgHeartRate) {
        parent::__construct($type, $durationMin, $caloriesBurned, $date);
        
        $this->distanceKm = $distanceKm;
        $this->avgHeartRate = $avgHeartRate;
    }

    public function getInfo(): string {
        $baseInfo = parent::getInfo();
        return $baseInfo . " Відстань: {$this->distanceKm} км, Середній пульс: {$this->avgHeartRate} уд/хв.";
    }

    public function getDistanceKm(): float {
        return $this->distanceKm;
    }

    public function getAvgHeartRate(): int {
        return $this->avgHeartRate;
    }
}