<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SearchRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Temporarily disable rate limiting for debugging
        return $next($request);
        
        // Only apply rate limiting when an actual search is performed
        if (!$request->has('query') || empty($request->query('query'))) {
            return $next($request);
        }
        
        $ip = $request->ip();
        $cacheKey = 'search_last_attempt_' . $ip;
        
        // Get the timestamp of the last search attempt
        $lastAttempt = Cache::get($cacheKey);
        $now = now()->timestamp;
        
        // If a search was performed in the last 5 seconds
        if ($lastAttempt && ($now - $lastAttempt) < 5) {
            $waitTime = 5 - ($now - $lastAttempt);
            
            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Too many search requests.',
                    'retry_after' => $waitTime
                ], 429);
            }
            
            // For web requests
            return response()->view('errors.429', [
                'seconds' => $waitTime,
                'message' => "Please wait {$waitTime} seconds before searching again."
            ], 429);
        }
        
        // Store the current timestamp
        Cache::put($cacheKey, $now, 10); // Keep for 10 seconds (longer than our 5 second limit)
        
        return $next($request);
    }
}