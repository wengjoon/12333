<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MontagVerification
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
        
        // Only add monetag meta tag to HTML responses
        if (is_a($response, 'Illuminate\Http\Response') && 
            str_contains($response->headers->get('Content-Type') ?? '', 'text/html')) {
            
            $content = $response->getContent();
            
            // Find the <head> tag to insert Monetag verification
            $pos = strpos($content, '<head>');
            if ($pos !== false) {
                $newContent = substr($content, 0, $pos + 6) . 
                              '<meta name="monetag" content="c9ad0f38532cc0734df4e589dd2b99a4">' . 
                              substr($content, $pos + 6);
                $response->setContent($newContent);
            }
        }
        
        return $response;
    }
}