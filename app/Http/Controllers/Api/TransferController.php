<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Services\FirebaseService;
use App\Models\TransactionModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    /**
     * Execute balance transfer between users
     */
    public function transfer(Request $request, FirebaseService $firebaseService)
    {
        $request->validate([
            'phone' => 'required|exists:users,phone',
            'amount' => 'required|numeric|min:1',
        ]);

        $sender = Auth::user();
        $receiverPhone = $request->phone;
        $amount = $request->amount;

        $receiver = User::where('phone', $receiverPhone)->first();
        $receiverId = $receiver->id;

        if ($sender->id == $receiverId) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Tidak dapat mentransfer ke diri sendiri.',
                'data' => null
            ], 400);
        }

        if ($sender->saldo < $amount) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi.',
                'data' => null
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Refresh sender to get latest saldo and lock it
            $sender = User::where('id', $sender->id)->lockForUpdate()->first();
            
            if ($sender->saldo < $amount) {
                throw new \Exception('Saldo tidak mencukupi.');
            }

            $receiver = User::where('id', $receiverId)->lockForUpdate()->first();

            // Deduct from sender
            $sender->saldo -= $amount;
            $sender->save();

            // Add to receiver
            $receiver->saldo += $amount;
            $receiver->save();

            // Create transaction for sender
            $transactionCodeSender = 'TRF-OUT-' . time() . '-' . $sender->id;
            TransactionModel::create([
                'transaction_code' => $transactionCodeSender,
                'transaction_date' => now()->format('Y-m-d'),
                'transaction_time' => now()->format('H:i:s'),
                'transaction_type' => 'transfer_out',
                'transaction_provider' => 'INTERNAL',
                'transaction_number' => $receiver->phone ?: $receiver->email,
                'transaction_sku' => 'TRANSFER_BALANCE',
                'transaction_total' => $amount,
                'transaction_message' => 'Transfer ke ' . $receiver->name,
                'transaction_status' => 'Sukses',
                'transaction_sn' => 'Transfer ke ' . $receiver->name,
                'transaction_product_name' => 'Transfer Saldo',
                'transaction_user_id' => $sender->id,
            ]);

            // Create transaction for receiver
            $transactionCodeReceiver = 'TRF-IN-' . time() . '-' . $receiver->id;
            TransactionModel::create([
                'transaction_code' => $transactionCodeReceiver,
                'transaction_date' => now()->format('Y-m-d'),
                'transaction_time' => now()->format('H:i:s'),
                'transaction_type' => 'transfer_in',
                'transaction_provider' => 'INTERNAL',
                'transaction_number' => $sender->phone ?: $sender->email,
                'transaction_sku' => 'TRANSFER_BALANCE',
                'transaction_total' => $amount,
                'transaction_message' => 'Transfer dari ' . $sender->name,
                'transaction_status' => 'Sukses',
                'transaction_sn' => 'Transfer dari ' . $sender->name,
                'transaction_product_name' => 'Transfer Saldo',
                'transaction_user_id' => $receiver->id,
            ]);

            DB::commit();

            // Send FCM notification to receiver (non-blocking for the transaction)
            try {
                $firebaseService->sendNotificationToUser(
                    $receiver,
                    'Dana Diterima!',
                    "Anda menerima transfer sebesar Rp " . number_format($amount, 0, ',', '.') . " dari " . $sender->name,
                    [
                        'type' => 'transfer_in',
                        'amount' => $amount,
                        'sender_name' => $sender->name,
                        'transaction_code' => $transactionCodeReceiver
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('FCM notification failed for transfer: ' . $e->getMessage());
            }

            return new ApiResponseResource([
                'status' => 'success',
                'message' => 'Transfer berhasil.',
                'data' => [
                    'amount' => $amount,
                    'receiver' => $receiver->name,
                    'new_balance' => $sender->saldo,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer failed: ' . $e->getMessage());

            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Transfer gagal: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
