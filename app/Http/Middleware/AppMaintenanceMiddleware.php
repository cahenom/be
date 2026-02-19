<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppMaintenanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if current request is version check, allow it
        if ($request->routeIs('app-config.version-check')) {
            return $next($request);
        }

        $platform = $request->header('X-App-Platform', 'android');
        $currentAppVersion = $request->header('X-App-Version');

        // Check Maintenance
        $isMaintenance = AppConfig::getValue('is_maintenance', 'false') === 'true';
        if ($isMaintenance) {
            $message = AppConfig::getValue('maintenance_message', 'Aplikasi sedang dalam pemeliharaan rutin.');
            return response()->json([
                'success' => false,
                'message' => $message,
                'is_maintenance' => true
            ], 503);
        }

        // Check Force Update if version is provided
        if ($currentAppVersion) {
            $minVersion = AppConfig::getValue("min_{$platform}_version", '1.0.0');
            if (version_compare($currentAppVersion, $minVersion, '<')) {
                return response()->json([
                    'success' => false,
                    'message' => "Versi aplikasi Anda ({$currentAppVersion}) sudah terlalu lama. Silakan perbarui ke versi {$minVersion} atau yang lebih baru.",
                    'force_update' => true,
                    'min_version' => $minVersion
                ], 426); // 426 Upgrade Required
            }
        }

        return $next($request);
    }
}