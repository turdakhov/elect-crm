<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ 3D-плана к API: один общий токен из .env (PLAN3D_TOKEN).
 *
 * Клиент у этого API ровно один — сервер 3D-плана, поэтому пользовательские
 * токены (Sanctum) были бы лишней сущностью. Токена в .env нет — API закрыт,
 * а не открыт: пустая строка совпала бы с пустым заголовком.
 */
class Plan3dToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.plan3d.token');
        if ($token === '') {
            return response()->json(['message' => 'Связь с 3D-планом не настроена: в .env CRM нет PLAN3D_TOKEN.'], 503);
        }
        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Неверный токен 3D-плана.'], 401);
        }

        return $next($request);
    }
}
