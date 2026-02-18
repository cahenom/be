<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppConfig;

class AppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            ['key' => 'min_android_version', 'value' => '1.0.0'],
            ['key' => 'latest_android_version', 'value' => '1.0.0'],
            ['key' => 'android_update_url', 'value' => 'https://play.google.com/store/apps/details?id=com.punya_kios'],
            ['key' => 'min_ios_version', 'value' => '1.0.0'],
            ['key' => 'latest_ios_version', 'value' => '1.0.0'],
            ['key' => 'ios_update_url', 'value' => 'https://apps.apple.com/app/punyakios'],
            ['key' => 'is_maintenance', 'value' => 'false'],
            ['key' => 'maintenance_message', 'value' => 'Aplikasi sedang dalam pemeliharaan rutin. Mohon tunggu beberapa saat.'],
        ];

        foreach ($configs as $config) {
            AppConfig::updateOrCreate(['key' => $config['key']], ['value' => $config['value']]);
        }
    }
}
