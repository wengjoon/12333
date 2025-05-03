namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
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
        $response = $next($request);
        
        // Only add CSP headers to HTML responses
        if (!$this->isHtmlResponse($response)) {
            return $response;
        }
        
        $cspHeader = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
            "img-src 'self' data: https://image.tmdb.org; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "connect-src 'self' https://api.themoviedb.org; " .
            "frame-src 'self' https://autoembed.co; " .
            "object-src 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self';";
        
        $response->headers->set('Content-Security-Policy', $cspHeader);
        
        return $response;
    }
    
    /**
     * Determine if the given response is an HTML response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function isHtmlResponse(Response $response): bool
    {
        return $response->headers->get('Content-Type') && 
               strpos($response->headers->get('Content-Type'), 'text/html') !== false;
    }
}
