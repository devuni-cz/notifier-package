<?php

namespace Devuni\Notifier\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBackupToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Backup-Token', $request->query('token'));

        if ($token !== config('notifier.backup_code')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
