<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\TransactionModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Ambil & update profil user
     */
    public function profile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return new ApiResponseResource([
                'status' => 'false',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Update profile jika POST + ada data
        if ($request->isMethod('post') && count($request->all()) > 0) {

            $validated = $request->validate([
                'name'      => 'sometimes|string|max:100',
                'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            ]);

            $user->update($validated);

            return new ApiResponseResource([
                'status'  => 'success',
                'message' => 'Profile berhasil didapatkan.',
                'data'    =>  $user,
            ]);
        }

        // Ambil profil user
        return response()->json([
            'status'  => true,
            'message' => 'User profile fetched.',
            'data'    => $user,
        ]);
    }

   /**
 * Ambil transaksi user (Dummy / Skeleton)
 */
public function transactions(Request $request)
{
    $user = $request->user(); // Sanctum auto detect

    if (!$user) {
        return new ApiResponseResource([
            'status' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }
    // Ambil semua transaksi user
    $transactions = TransactionModel::where('transaction_user_id', $user->id)->get();

    return new ApiResponseResource([
        'status'  => true,
        'message' => 'User transactions berhasil didapatkan.',
        'data'    => $transactions,
    ]);
}
}