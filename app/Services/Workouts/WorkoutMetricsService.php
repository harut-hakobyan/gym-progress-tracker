<?php

namespace App\Services\Workouts;

class WorkoutMetricsService
{
    public function roundWeight(float $weight): float
    {
        return round($weight * 2) / 2;
    }

    public function setVolume(float $weight, int $repetitions): float
    {
        return $this->roundWeight($weight) * $repetitions;
    }

    public function estimatedOneRepMax(float $weight, int $repetitions): float
    {
        if ($repetitions <= 1) {
            return $this->roundWeight($weight);
        }

        if ($repetitions > 15) {
            return $this->roundWeight($weight);
        }

        return $this->roundWeight($weight * (1 + ($repetitions / 30)));
    }
}
