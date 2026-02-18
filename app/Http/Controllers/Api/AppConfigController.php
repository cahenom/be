<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function versionCheck(Request $request)
    {
        $platform = $request->query('platform', 'android'); // android or ios
        
        $minVersion = AppConfig::getValue("min_{$platform}_version", '1.0.0');
        $latestVersion = AppConfig::getValue("latest_{$platform}_version", '1.0.0');
        $updateUrl = AppConfig::getValue("{$platform}_update_url", 'https://play.google.com/store/apps/details?id=com.punya_kios');
        $isMaintenance = AppConfig::getValue('is_maintenance', 'false') === 'true';
        $maintenanceMsg = AppConfig::getValue('maintenance_message', 'Aplikasi sedang dalam pemeliharaan rutin.');

        return response()->json([
            'success' => true,
            'data' => [
                'min_version' => $minVersion,
                'latest_version' => $latestVersion,
                'update_url' => $updateUrl,
                'is_maintenance' => $isMaintenance,
                'maintenance_message' => $maintenanceMsg,
            ]
        ]);
    }
}
