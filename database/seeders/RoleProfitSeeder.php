<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleProfitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    DB::table('role_profit_settings')->insert([
        [
            'role_id' => 1, // user
            'markup_percent' => 5,
            'markup_min' => 0,
            'markup_max' => 0,
            'is_default' => true,
        ],
        [
            'role_id' => 2, // reseller
            'markup_percent' => 3,
            'markup_min' => 0,
            'markup_max' => 0,
            'is_default' => false,
        ],
        [
            'role_id' => 3, // agen
            'markup_percent' => 2,
            'markup_min' => 0,
            'markup_max' => 0,
            'is_default' => false,
        ],
    ]);
}

}
