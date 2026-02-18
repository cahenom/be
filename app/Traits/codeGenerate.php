<?php

namespace App\Traits;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

trait CodeGenerate
{

    public function getCode()
{
    $lock = Cache::lock('code_generate_lock', 10);

    try {
        $lock->block(5);

        $q = DB::table('code_generate')
            ->select(DB::raw('MAX(RIGHT(code,9)) as kd_max'))
            ->first();

        $prefix = 'INV' . date('ym');

        if ($q && $q->kd_max !== null) {
            $tmp = ((int)$q->kd_max) + 1;
            $kd_num = sprintf("%09d", $tmp);
        } else {
            $kd_num = "000000001";
        }

        $kd = $prefix . '-' . $kd_num;

        DB::table('code_generate')->insert([
            'code' => $kd,
            'date_generate' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $kd;

    } finally {
        optional($lock)->release();
    }
}

}
