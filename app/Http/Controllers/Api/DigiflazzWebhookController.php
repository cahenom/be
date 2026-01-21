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

        // Log webhook data for debugging
        \Log::info('Digiflazz Webhook Received:', [
            'user_agent' => $ua,
            'type' => $type,
            'ref_id' => $data['ref_id'] ?? null,
            'status' => $data['status'] ?? null,
            'message' => $data['message'] ?? null,
            'sn' => $data['sn'] ?? null,
            'event' => $request->header('X-Digiflazz-Event'),
        ]);

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

        // Get product information to determine provider
        $productProvider = 'Digiflazz'; // Default fallback

        if ($type === 'Prepaid') {
            $product = \App\Models\ProductPrepaid::findProductBySKU($data['buyer_sku_code'] ?? '')->first();
            $productProvider = $product ? $product->product_provider : 'Digiflazz';
        } else {
            $product = \App\Models\ProductPasca::findBySKU($data['buyer_sku_code'] ?? '')->first();
            $productProvider = $product ? $product->product_provider : 'Digiflazz';
        }

        $payloadToSave = [
            'transaction_code'     => $data['ref_id'],
            'transaction_date'     => now()->toDateString(),
            'transaction_time'     => now()->toTimeString(),
            'transaction_type'     => $type,
            'transaction_provider' => $data['brand'] ?? $productProvider,
            'transaction_number'   => $data['customer_no'] ?? null,
            'transaction_sku'      => $data['buyer_sku_code'] ?? null,

            // 💰 FINANCE - PRESERVE ORIGINAL TRANSACTION_TOTAL
            'transaction_cost'     => $cost,
            'transaction_profit'   => $profit,

            'transaction_message'  => $data['message'] ?? null,
            'transaction_status'   => $data['status'] ?? null,
            'transaction_sn'       => $data['sn'] ?? null,  // Add SN field from webhook
        ];

        if ($trx) {
            // Only update fields that might change, preserve original transaction_total
            $trx->update([
                'transaction_message' => $data['message'] ?? null,
                'transaction_status' => $data['status'] ?? null,
                'transaction_sn' => $data['sn'] ?? null,
                'transaction_cost' => $cost,
                'transaction_profit' => $profit,
            ]);
            \Log::info('Prepaid transaction updated via webhook:', [
                'ref_id' => $data['ref_id'],
                'status' => $data['status'],
                'message' => $data['message'],
                'sn' => $data['sn'],
            ]);
        } else {
            // For new transactions, set the transaction_total from the original creation
            $payloadToSave['transaction_total'] = $selling; // Only for new transactions
            TransactionModel::create($payloadToSave);
            \Log::info('New prepaid transaction created via webhook:', [
                'ref_id' => $data['ref_id'],
                'status' => $data['status'],
                'message' => $data['message'],
                'sn' => $data['sn'],
            ]);
        }

        // Also update postpaid transaction if exists
        $postpaidTrx = \App\Models\PascaTransaction::where('ref_id', $data['ref_id'])->first();
        if ($postpaidTrx) {
            $event = $request->header('X-Digiflazz-Event') ?: 'update'; // Default to update if header not present

            if ($event === 'create') {
                // For create event, update inquiry status
                $postpaidTrx->update([
                    'status_inquiry' => $this->mapApiStatusToEnum($data['status'] ?? 'failed'),
                    'message_inquiry' => $data['message'] ?? null,
                    'sn' => $data['sn'] ?? null, // Add SN field for postpaid
                ]);
                \Log::info('Postpaid inquiry transaction updated via webhook:', [
                    'ref_id' => $data['ref_id'],
                    'status' => $data['status'],
                    'message' => $data['message'],
                    'sn' => $data['sn'],
                    'event' => $event,
                ]);
            } else {
                // For update event, update payment status
                $postpaidTrx->update([
                    'status_payment' => $this->mapApiStatusToEnum($data['status'] ?? 'failed'),
                    'message_payment' => $data['message'] ?? null,
                    'sn' => $data['sn'] ?? null, // Add SN field for postpaid
                ]);
                \Log::info('Postpaid payment transaction updated via webhook:', [
                    'ref_id' => $data['ref_id'],
                    'status' => $data['status'],
                    'message' => $data['message'],
                    'sn' => $data['sn'],
                    'event' => $event,
                ]);
            }
        }

        Log::info('Digiflazz webhook OK', [
            'ref_id' => $data['ref_id'],
            'status' => $data['status'],
            'profit' => $profit
        ]);

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Map API status to our enum values
     */
    private function mapApiStatusToEnum($apiStatus)
    {
        $apiStatus = strtolower($apiStatus);

        switch ($apiStatus) {
            case 'sukses':
            case 'success':
                return 'success';
            case 'pending':
            case 'proses':
                return 'pending';
            case 'gagal':
            case 'failed':
            case 'error':
            default:
                return 'failed';
        }
    }
}
