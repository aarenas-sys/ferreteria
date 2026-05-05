<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Tiempo de inactividad permitido en minutos (debe coincidir con SESSION_LIFETIME)
     */
    private const INACTIVITY_TIMEOUT = 2;

    /**
     * Rutas públicas que NO deben ser afectadas por este middleware
     */
    private const PUBLIC_ROUTES = [
        '/',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/email/verify',
    ];

    /**
     * Rutas que NO deben actualizar last_activity
     */
    private const EXCLUDED_PATHS = [
        'favicon.ico',
        '.well-known',
        'artisan',
        '/logout',
        '/session/ping',
        '/js/',
        '/css/',
        '/images/',
        '.png',
        '.jpg',
        '.css',
        '.js',
        '.woff',
        '.map',
    ];

    /**
     * Manejo de la solicitud
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Solo se aplica a usuarios autenticados
        if (!Auth::check()) {
            return $next($request);
        }

        // 2. Omitir rutas públicas completamente
        if ($this->isPublicRoute($request->getPathInfo())) {
            return $next($request);
        }

        // 3. Permitir logout sin verificación
        $path = $request->getPathInfo();
        if ($path === '/logout') {
            return $next($request);
        }

        // 4. Verificación de timeout para usuarios autenticados en rutas protegidas
        if (Auth::check()) {
            $lastActivityKey = 'last_activity';
            $lastActivity = Session::get($lastActivityKey);
            $currentTime = time();
            $timeoutInSeconds = self::INACTIVITY_TIMEOUT * 60;

            // Verificar si la sesión ha expirado por inactividad
            if ($lastActivity !== null) {
                $elapsedTime = $currentTime - $lastActivity;

                if ($elapsedTime > $timeoutInSeconds) {
                    Log::info('Session timeout - User logout', [
                        'user_id' => Auth::id(),
                        'elapsed_seconds' => $elapsedTime,
                        'timeout_seconds' => $timeoutInSeconds,
                        'path' => $path,
                    ]);

                    Auth::logout();
                    Session::invalidate();
                    Session::regenerateToken();

                    // Si es una solicitud AJAX/fetch, retornar JSON con 401
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Session expired'], 401);
                    }

                    return redirect('/login')
                        ->with('warning', 'Tu sesión expiró por inactividad. Por favor, vuelve a iniciar sesión.');
                }
            }

            // Actualizar last_activity SOLO en rutas reales (no en assets) e ignorar /session/ping
            if (!$this->isExcludedPath($path) && $path !== '/session/ping') {
                Session::put($lastActivityKey, $currentTime);
                Log::debug('Session activity updated', [
                    'user_id' => Auth::id(),
                    'path' => $path,
                    'time' => date('Y-m-d H:i:s', $currentTime),
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Verificar si la ruta es pública (no requiere autenticación)
     * Las rutas públicas no deben pasar por validación de timeout
     */
    private function isPublicRoute(string $path): bool
    {
        // Comparar ruta exacta primero
        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if ($path === $publicRoute) {
                return true;
            }
            // Comparar prefijos para rutas como /forgot-password/...
            if ($publicRoute !== '/' && str_starts_with($path, $publicRoute . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si la ruta debe ser excluida de actualización de actividad
     */
    private function isExcludedPath(string $path): bool
    {
        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            if (str_contains(strtolower($path), strtolower($excludedPath))) {
                return true;
            }
        }
        return false;
    }
}

