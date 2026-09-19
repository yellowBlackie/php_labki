<?php
class TrainingLog {
    private array $workouts = [];

    public function addWorkout(Workout $workout): void {
        $this->workouts[] = $workout;
    }

    public function totalCalories(): int {
        $total = 0;
        foreach ($this->workouts as $workout) {
            $total += $workout->getCaloriesBurned();
        }
        return $total;
    }

    public function findByType(string $type): array {
        $found = [];
        foreach ($this->workouts as $workout) {
            if (strcasecmp($workout->getType(), $type) === 0) {
                $found[] = $workout;
            }
        }
        return $found;
    }

    public function getAllWorkouts(): array {
        return $this->workouts;
    }
}