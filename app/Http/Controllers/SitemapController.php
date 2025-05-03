<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\TmdbService;

class SitemapController extends Controller
{
    /**
     * The movie service
     */
    protected $movieService;
    
    /**
     * Constructor to initialize the service
     */
    public function __construct(TmdbService $movieService)
    {
        $this->movieService = $movieService;
    }

    /**
     * Generate and return the sitemap XML
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Turn off any output buffering that might be active
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Cache the sitemap for 24 hours (86400 seconds)
        $content = Cache::remember('sitemap', 86400, function () {
            return $this->generateSitemap();
        });
        
        // Set the content type header
        header('Content-Type: text/xml; charset=UTF-8');
        
        // Prevent any further content from being added
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        
        // Output the XML directly and exit to bypass any middleware or other processes
        echo $content;
        exit;
    }
    
    /**
     * Generate sitemap.xml content
     * 
     * @return string
     */
    protected function generateSitemap()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        // Add home page
        $sitemap .= $this->createUrlEntry(
            route('movies.index'),
            Carbon::now()->toW3cString(),
            'daily',
            '1.0'
        );
        
        // Add popular movies - using pre-defined terms for OMDB
        $popularMovies = $this->getPopularMovies();
        foreach ($popularMovies as $movie) {
            $sitemap .= $this->createUrlEntry(
                route('movies.show', ['id' => $movie['id']]),
                isset($movie['release_date']) ? Carbon::parse($movie['release_date'])->toW3cString() : Carbon::now()->toW3cString(),
                'weekly',
                '0.8'
            );
        }
        
        // Add top rated movies
        $topRatedMovies = $this->getTopRatedMovies();
        foreach ($topRatedMovies as $movie) {
            // Skip if already added from popular movies
            if (!$this->isMovieInList($movie, $popularMovies)) {
                $sitemap .= $this->createUrlEntry(
                    route('movies.show', ['id' => $movie['id']]),
                    isset($movie['release_date']) ? Carbon::parse($movie['release_date'])->toW3cString() : Carbon::now()->toW3cString(),
                    'weekly',
                    '0.8'
                );
            }
        }
        
        $sitemap .= '</urlset>';
        
        return $sitemap;
    }
    
    /**
     * Create a URL entry for the sitemap
     * 
     * @param string $loc
     * @param string $lastmod
     * @param string $changefreq
     * @param string $priority
     * @return string
     */
    protected function createUrlEntry($loc, $lastmod, $changefreq, $priority)
    {
        return "\t<url>\n" .
               "\t\t<loc>" . htmlspecialchars($loc) . "</loc>\n" .
               "\t\t<lastmod>" . $lastmod . "</lastmod>\n" .
               "\t\t<changefreq>" . $changefreq . "</changefreq>\n" .
               "\t\t<priority>" . $priority . "</priority>\n" .
               "\t</url>\n";
    }
    
    /**
     * Get popular movies from OMDB API
     * 
     * @param int $limit
     * @return array
     */
    protected function getPopularMovies($limit = 40)
    {
        // Popular search terms for OMDB
        $terms = ['action', 'comedy', 'drama', 'sci-fi', 'thriller'];
        $movies = [];
        
        foreach ($terms as $term) {
            if (count($movies) >= $limit) {
                break;
            }
            
            $response = $this->movieService->searchMovies($term, 1);
            if (isset($response['results']) && is_array($response['results'])) {
                foreach ($response['results'] as $movie) {
                    $movies[] = $movie;
                    if (count($movies) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return $movies;
    }
    
    /**
     * Get top rated movies from OMDB API
     * 
     * @param int $limit
     * @return array
     */
    protected function getTopRatedMovies($limit = 20)
    {
        $response = $this->movieService->getTopRatedMovies();
        return array_slice($response['results'] ?? [], 0, $limit);
    }
    
    /**
     * Check if a movie is already in a list
     * 
     * @param array $movie
     * @param array $list
     * @return bool
     */
    protected function isMovieInList($movie, $list)
    {
        foreach ($list as $item) {
            if ($item['id'] === $movie['id']) {
                return true;
            }
        }
        
        return false;
    }
}