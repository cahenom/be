<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$info = [];
try {
    $info['code_generate_cols'] = Schema::getColumnListing('code_generate');
    $info['code_generate_count'] = DB::table('code_generate')->count();
    $info['code_generate_sample'] = DB::table('code_generate')->latest()->limit(5)->get();
} catch (\Exception $e) {
    $info['error'] = $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT);
