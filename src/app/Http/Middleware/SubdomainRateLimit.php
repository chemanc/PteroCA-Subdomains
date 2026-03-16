<?php

namespace App\Http\Middleware;

use App\Models\Subdomain;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubdomainRateLimit
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request.
     *
     * Rate limits subdomain API requests per authenticated user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = (int) Subdomain::getSetting('rate_limit', config('subdomains.rate_limit', 5));
        $key = 'subdomain_rate_limit:' . ($request->user()?->id ?? $request->ip());

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'message' => __('subdomains::subdomains.generic_error'),
                'retry_after' => $retryAfter,
            ], 429);
        }

        $this->limiter->hit($key, 60); // 1 minute decay

        return $next($request);
    }
}
