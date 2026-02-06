<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:audit-balances {--fix : Automatically fix discrepancies by updating the user saldo}';

    protected $description = 'Verify that user balances match their transaction and deposit history.';

    public function handle()
    {
        $users = \App\Models\User::all();
        $this->info("Starting balance audit for " . $users->count() . " users...");

        $headers = ['ID', 'Name', 'Current Saldo', 'Expected Saldo', 'Difference', 'Status'];
        $data = [];

        foreach ($users as $user) {
            // 1. Sum paid deposits
            $totalDeposits = \App\Models\Deposit::where('user_id', $user->id)
                ->where('status', 'paid')
                ->sum('amount');

            // 2. Sum transactions that were paid (Sukses, Pending)
            // Transactions that are 'Gagal' were either never charged or were refunded,
            // so they shouldn't count towards the "spent" amount in a simple reconciliation.
            $totalSpent = \App\Models\TransactionModel::where('transaction_user_id', $user->id)
                ->whereIn('transaction_status', ['Sukses', 'Pending', 'Processing', 'Sukses']) // Matches logic in DigiflazController
                ->sum('transaction_total');

            // 3. Postpaid transactions (amount_total)
            $totalPostpaid = \App\Models\PascaTransaction::where('user_id', $user->id)
                ->whereIn('status_payment', ['success', 'pending'])
                ->sum('amount_total');

            $expectedSaldo = (float)$totalDeposits - (float)$totalSpent - (float)$totalPostpaid;
            $currentSaldo = (float)$user->saldo;
            $diff = $expectedSaldo - $currentSaldo;

            $status = $diff == 0 ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';

            if ($diff != 0 && $this->option('fix')) {
                $user->saldo = $expectedSaldo;
                $user->save();
                $status = '<fg=yellow>FIXED</>';
            }

            $data[] = [
                $user->id,
                $user->name,
                number_format($currentSaldo, 2),
                number_format($expectedSaldo, 2),
                number_format($diff, 2),
                $status
            ];
        }

        $this->table($headers, $data);
        $this->info("Audit completed.");
    }
}
