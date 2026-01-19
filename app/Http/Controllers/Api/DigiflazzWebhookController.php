<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionModel;

class DigiflazzWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Raw body untuk validasi signature
        $raw = $request->getContent();
        $secret = env('DIGIFLAZZ_WEBHOOK_SECRET');

        if (!empty($secret)) {
            $expected = 'sha1=' . hash_hmac('sha1', $raw, $secret);
            $signature = $request->header('X-Hub-Signature');

            if ($signature !== $expected) {
                Log::warning('Digiflazz webhook signature mismatch', [
                    'got' => $signature,
                    'expected' => $expected,
                ]);

                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        // 2. Ambil payload Digiflazz
        $payload = $request->json()->all();

        if (!isset($payload['data'])) {
            Log::info('Webhook ping', $payload);
            return response()->json(['message' => 'OK'], 200);
        }

        $data = $payload['data'];
        $ua = $request->header('User-Agent');
        $type = ($ua === 'Digiflazz-Pasca-Hookshot') ? 'Pasca' : 'Prepaid';

        // 3. Tentukan harga modal dan harga jual
        $cost = 0;
        $selling = 0;

        if ($type === 'Prepaid') {
            // Prepaid → price = modal asli
            $cost = $data['price'] ?? 0;
            $selling = $cost; // jual sebelum markup (asli dari digiflazz)
        } else {
            // Pascabayar
            $cost = ($data['price'] ?? 0) + ($data['admin'] ?? 0);
            $selling = $data['selling_price'] ?? $cost;
        }

        // 4. Hitung profit (keuntungan)
        $profit = max($selling - $cost, 0);

        // 5. Cari transaksi berdasarkan ref_id
        $trx = TransactionModel::where('transaction_code', $data['ref_id'])->first();

        $payloadToSave = [
            'transaction_code'     => $data['ref_id'],
            'transaction_date'     => now()->toDateString(),
            'transaction_time'     => now()->toTimeString(),
            'transaction_type'     => $type,
            'transaction_provider' => $data['brand'] ?? 'UNKNOWN',
            'transaction_number'   => $data['customer_no'] ?? null,
            'transaction_sku'      => $data['buyer_sku_code'] ?? null,

            // 💰 FINANCE
            'transaction_cost'     => $cost,
            'transaction_total'    => $selling,
            'transaction_profit'   => $profit,

            'transaction_message'  => $data['message'] ?? null,
            'transaction_status'   => $data['status'] ?? null,
        ];

        if ($trx) {
            $trx->update($payloadToSave);
        } else {
            TransactionModel::create($payloadToSave);
        }

        Log::info('Digiflazz webhook OK', [
            'ref_id' => $data['ref_id'],
            'status' => $data['status'],
            'profit' => $profit
        ]);

        return response()->json(['message' => 'OK'], 200);
    }
}
