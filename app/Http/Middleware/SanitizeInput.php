<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value)) {
                $value = trim($value);

                // Sanitize auth-related string fields (prevent XSS/injection in stored data)
                if (in_array($key, ['email', 'name'])) {
                    $value = strip_tags($value);
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }

                // Sanitize common numeric target fields (phones, customer numbers, etc.)
                if (in_array($key, ['customer_no', 'destination', 'target', 'phone', 'nomor'])) {
                    $value = preg_replace('/[^0-9]/', '', $value);
                }
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
