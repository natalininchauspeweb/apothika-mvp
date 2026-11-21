<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que restringe el acceso a una ruta según el rol del usuario autenticado.
 * Uso en rutas: ->middleware('role:patient') o ->middleware('role:pharmacy')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, devolvemos 401 Unauthorized
        if (!$user) {
            return response()->json([
                'message' => 'Debés iniciar sesión para acceder a este recurso.',
            ], 401);
        }

        // Si el rol del usuario no está dentro de los permitidos, devolvemos 403 Forbidden
        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'No tenés permisos para acceder a esta sección.',
            ], 403);
        }

        // Todo correcto: dejamos pasar la petición al siguiente eslabón (otro middleware o el controller)
        return $next($request);
    }
}