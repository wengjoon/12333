<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\TmdbService;

class CacheTopRatedMovies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:cache-top-rated {--force : Force cache refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache top rated movies from OMDB API';

    /**
     * The movie service
     */
    protected $movieService;

    /**
     * Create a new command instance.
     */
    public function __construct(TmdbService $movieService)
    {
        parent::__construct();
        $this->movieService = $movieService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to cache top rated movies...');
        
        $cacheKey = 'top_rated_movies';
        
        // Force cache refresh if requested
        if ($this->option('force')) {
            $this->info('Forcing cache refresh...');
            Cache::forget($cacheKey);
        }
        
        try {
            // Get top rated movies response
            $response = $this->movieService->getTopRatedMovies();
            $movies = $response['results'] ?? [];
            
            if (empty($movies)) {
                $this->error('No top rated movies returned from OMDB API.');
                return 1;
            }
            
            // Process first 8 movies to make sure we have all details
            $this->output->progressStart(min(8, count($movies)));
            $processedMovies = [];
            $count = 0;
            
            foreach ($movies as $movie) {
                if ($count >= 8) break; // Only process first 8 movies
                
                // Movies from topRated should already have full details
                // but let's ensure they have directors and cast
                if (!isset($movie['directors']) || empty($movie['directors'])) {
                    $details = $this->movieService->getMovieDetails($movie['id']);
                    $movie = array_merge($movie, $details);
                }
                
                $processedMovies[] = $movie;
                $count++;
                
                $this->output->progressAdvance();
            }
            
            $this->output->progressFinish();
            
            // Store in cache for one day (could be shorter during transition)
            Cache::put($cacheKey, $processedMovies, now()->addMinutes(1440));
            
            $this->info('Successfully cached ' . count($processedMovies) . ' top rated movies from OMDB API.');
            $this->info('First movie in cache: ' . ($processedMovies[0]['title'] ?? 'Unknown'));
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to fetch top rated movies: " . $e->getMessage());
            return 1;
        }
    }
}