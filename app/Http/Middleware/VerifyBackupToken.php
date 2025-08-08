<?php

namespace Devuni\Notifier\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBackupToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Backup-Code') ?? $request->query('backup_code');

        if ($token !== config('notifier.backup_code')) {
            abort(403);
        }

        return $next($request);
    }
}
