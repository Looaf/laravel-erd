<?php

namespace Looaf\LaravelErd\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ErdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if ERD is enabled
        if (!config('erd.enabled', true)) {
            return $this->unauthorizedResponse('ERD functionality is disabled');
        }

        // Check if current environment is allowed
        if (!$this->isEnvironmentAllowed()) {
            return $this->unauthorizedResponse('ERD access is not allowed in this environment');
        }

        return $next($request);
    }

    /**
     * Check if the current environment is allowed to access ERD
     *
     * @return bool
     */
    protected function isEnvironmentAllowed(): bool
    {
        $allowedEnvironments = config('erd.environments', ['local', 'testing']);
        $currentEnvironment = app()->environment();

        // Allow all environments if configured with wildcard
        if (in_array('*', $allowedEnvironments)) {
            return true;
        }

        return in_array($currentEnvironment, $allowedEnvironments);
    }

    /**
     * Return an unauthorized response
     *
     * @param string $message
     * @return \Illuminate\Http\Response
     */
    protected function unauthorizedResponse(string $message): Response
    {
        // For AJAX requests, return JSON response
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => 'Unauthorized access',
            ], 403);
        }

        // For regular requests, return 404 to avoid revealing the existence of the route
        abort(404);
    }
}