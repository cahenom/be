<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$num = '085123234311';
$t = DB::table('transaction')->where('transaction_number', $num)->first();

if ($t) {
    echo "Found Transaction with number $num:\n";
    print_r($t);
} else {
    echo "Number $num NOT FOUND in transaction table\n";
}
