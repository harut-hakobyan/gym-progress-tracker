<?php

namespace Tests\Unit;

use App\Services\Workouts\WorkoutMetricsService;
use Tests\TestCase;

class WorkoutMetricsServiceTest extends TestCase
{
    public function test_volume_is_calculated_correctly(): void
    {
        $service = app(WorkoutMetricsService::class);

        $this->assertSame(660.0, $service->setVolume(82.5, 8));
    }

    public function test_estimated_one_rep_max_is_calculated_with_epley(): void
    {
        $service = app(WorkoutMetricsService::class);

        $this->assertSame(104.5, $service->estimatedOneRepMax(82.5, 8));
        $this->assertSame(82.5, $service->estimatedOneRepMax(82.5, 1));
        $this->assertSame(82.5, $service->estimatedOneRepMax(82.5, 16));
    }
}
