<?php

namespace App\Traits;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

trait CodeGenerate
{

    public function getCode()
    {
        // Use a lock to prevent race conditions during code generation
        $lock = Cache::lock('code_generate_lock', 10);
        
        try {
            $lock->block(5); // Wait up to 5 seconds for the lock

            $q = DB::table('code_generate')->select(DB::raw('MAX(RIGHT(code,9)) as kd_max'))->first();
            $prx = 'INV-BL-' . date('y') . '-' . date('m') . '-';
            
            if ($q && $q->kd_max !== null) {
                $tmp = ((int)$q->kd_max) + 1;
                $kd_num = sprintf("%09s", $tmp);
            } else {
                $kd_num = "000000001";
            }

            // Add entropy: Shorter User ID (to keep total length manageable) + random string
            $userId = auth()->id() ?? 0;
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
            
            // Format: INV-BL-YY-MM-USERID-RAND-NUMBER
            // Better to keep total length reasonable for Digiflazz ref_id (usually max 40 chars)
            $kd = $prx . $userId . '-' . $random . '-' . $kd_num;

            DB::table('code_generate')->insert([
                'code'          => $kd,
                'date_generate' => Carbon::now()->format('Y-m-d'),
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now()
            ]);

            return $kd;
        } finally {
            $lock->release();
        }
    }
}
