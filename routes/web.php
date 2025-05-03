<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Api\MovieApiController;
use App\Http\Middleware\SearchRateLimiter; // Add this import

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', [MovieController::class, 'index'])->name('movies.index');
Route::get('/search', [MovieController::class, 'search'])
    ->middleware(SearchRateLimiter::class) // Use class directly instead of alias
    ->name('movies.search');
Route::get('/movie/{id}', [MovieController::class, 'show'])->name('movies.show');

// Sitemap route
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// If this API route exists
if (method_exists(MovieApiController::class, 'search')) {
    Route::get('/api/movies/search', [MovieApiController::class, 'search'])
        ->middleware(SearchRateLimiter::class); // Use class directly
}

Route::get('robots.txt', function () {
    $robotsContent = "# Sitemap for all bots
Sitemap: https://123moviespro.cc/sitemap.xml
Sitemap: https://123moviespro.cc/123moviespro_sitemap.xml
# Rules for all bots
User-agent: *
Disallow: /admin/           # Block admin areas
Disallow: /login/           # Block login pages
Allow: /                    # Allow everything else (optional, implied)
# Crawl delay for heavy SEO bots
User-agent: AhrefsBot
Crawl-delay: 5              # Reduced to 5 seconds for balance
User-agent: MJ12bot
Crawl-delay: 5
User-agent: SemrushBot
Crawl-delay: 5";
    
    return response($robotsContent)
        ->header('Content-Type', 'text/plain');
});


Route::get('/123moviespro_sitemap.xml', function () {
    $path = public_path('123moviespro_sitemap.xml');
    
    if (!file_exists($path)) {
        abort(404, 'Sitemap file not found at: ' . $path);
    }
    
    $contents = file_get_contents($path);
    return response($contents)
        ->header('Content-Type', 'application/xml');
});