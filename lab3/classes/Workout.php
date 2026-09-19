<?php
class Workout {
    protected string $type;
    protected int $durationMin;
    protected int $caloriesBurned;
    protected string $date;

    public function __construct(string $type, int $durationMin, int $caloriesBurned, string $date) {
        $this->type = $type;
        $this->durationMin = $durationMin;
        $this->caloriesBurned = $caloriesBurned;
        $this->date = $date;
    }

    public function getInfo(): string {
        return "Тренування: <strong>{$this->type}</strong> ({$this->date}), Тривалість: {$this->durationMin} хв, Калорії: {$this->caloriesBurned} ккал.";
    }

    public function getCaloriesBurned(): int {
        return $this->caloriesBurned;
    }

    public function getType(): string {
        return $this->type;
    }
    
    public function getDurationMin(): int {
        return $this->durationMin;
    }
}