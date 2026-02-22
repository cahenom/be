<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{
    protected $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    /**
     * Exchange points for saldo balance
     * Formula: 1 Point = Rp 1 Saldo
     */
    public function exchange(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:100',
        ]);

        $user = $request->user();
        $pointsToExchange = $request->points;

        if ($user->points < $pointsToExchange) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Poin tidak cukup',
                'data' => null
            ], 400);
        }

        try {
            DB::transaction(function () use ($user, $pointsToExchange) {
                // Deduct points
                $user->decrement('points', $pointsToExchange);
                
                // Add balance (1 point = 1 IDR)
                $user->increment('saldo', $pointsToExchange);
            });

            return new ApiResponseResource([
                'status' => 'success',
                'message' => "Berhasil menukar {$pointsToExchange} poin menjadi Rp " . number_format($pointsToExchange, 0, ',', '.'),
                'data' => [
                    'points_remaining' => $user->fresh()->points,
                    'new_balance' => $user->fresh()->saldo,
                ]
            ]);
        } catch (\Exception $e) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Gagal menukar poin: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
