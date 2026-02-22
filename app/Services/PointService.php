<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointService
{
    /**
     * Calculate points based on price
     */
    public function calculatePoints($price): int
    {
        return max(1, min(10, floor($price / 10000)));
    }

    /**
     * Award points to user
     */
    public function awardPoints(User $user, int $points): void
    {
        if ($points <= 0) return;

        DB::transaction(function () use ($user, $points) {
            $user->increment('points', $points);
        });
    }

    /**
     * Deduct points from user
     */
    public function deductPoints(User $user, int $points): bool
    {
        if ($user->points < $points) return false;

        DB::transaction(function () use ($user, $points) {
            $user->decrement('points', $points);
        });

        return true;
    }
}
