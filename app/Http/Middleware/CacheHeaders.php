<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHeaders
{
    /**
     * Cache periods for different types of resources.
     */
    protected $cachePeriods = [
        'image' => 31536000, // 1 year in seconds
        'font' => 31536000,  // 1 year in seconds
        'css' => 2592000,    // 30 days in seconds
        'js' => 2592000,     // 30 days in seconds
        'default' => 3600    // 1 hour in seconds
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only add cache headers for GET requests
        if (!$request->isMethod('GET')) {
            return $response;
        }
        
        // Don't cache if the response is an error
        if ($response->isServerError() || $response->isClientError()) {
            return $this->addNoCacheHeaders($response);
        }
        
        // Set cache headers based on resource type
        $path = $request->path();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'svg':
            case 'webp':
            case 'avif':
            case 'ico':
                return $this->addCacheHeaders($response, $this->cachePeriods['image']);
                
            case 'ttf':
            case 'otf':
            case 'woff':
            case 'woff2':
                return $this->addCacheHeaders($response, $this->cachePeriods['font']);
                
            case 'css':
                return $this->addCacheHeaders($response, $this->cachePeriods['css']);
                
            case 'js':
                return $this->addCacheHeaders($response, $this->cachePeriods['js']);
                
            default:
                // For HTML/PHP and other dynamic content
                if (empty($extension) || $extension == 'php' || $extension == 'html' || $extension == 'htm') {
                    return $this->addNoCacheHeaders($response);
                }
                
                // For other resources, use default caching period
                return $this->addCacheHeaders($response, $this->cachePeriods['default']);
        }
    }
    
    /**
     * Add cache headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAge  Cache duration in seconds
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCacheHeaders(Response $response, int $maxAge): Response
    {
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
        $response->headers->set('Pragma', 'public');
        
        // Set expiration time
        $expirationTime = gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT';
        $response->headers->set('Expires', $expirationTime);
        
        return $response;
    }
    
    /**
     * Add no-cache headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addNoCacheHeaders(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Wed, 11 Jan 1984 05:00:00 GMT');
        
        return $response;
    }
}