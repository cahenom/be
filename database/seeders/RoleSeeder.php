<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'roles_name' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'roles_name' => 'reseller',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'roles_name' => 'agen',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}