<?php

declare(strict_types=1);

namespace Devuni\Notifier\Middleware;

use Closure;
use Illuminate\Http\Request;
use Devuni\Notifier\Support\NotifierLogger;
use Symfony\Component\HttpFoundation\Response;
use Devuni\Notifier\Services\NotifierConfigService;

class VerifyNotifierTokenMiddleware
{
    public function __construct(
        private readonly NotifierConfigService $configService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $missingVariables = $this->configService->checkEnvironment();

        if (! empty($missingVariables)) {
            return response()->json([
                'success' => false,
                'message' => 'Server configuration incomplete.',
                'missing_variables' => $missingVariables,
            ], 500);
        }

        $expectedToken = config('notifier.backup_code');

        $providedToken = $request->header('X-Notifier-Token');

        if (empty($providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing authentication token.',
            ], 401);
        }

        if (! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
            ], 403);
        }

        return $next($request);
    }
}
