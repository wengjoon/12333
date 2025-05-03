<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbService;
use Illuminate\Support\Facades\Log;

class PrefetchMovies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:prefetch {--pages=2} {--type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prefetch and cache popular and top-rated movies';

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
        $pages = (int)$this->option('pages');
        $type = $this->option('type');
        
        $this->info('Starting to prefetch movies...');
        
        try {
            // Prefetch popular movies if type is 'all' or 'popular'
            if ($type === 'all' || $type === 'popular') {
                $this->prefetchPopular($pages);
            }
            
            // Prefetch top rated movies if type is 'all' or 'top_rated'
            if ($type === 'all' || $type === 'top_rated') {
                $this->prefetchTopRated($pages);
            }
            
            $this->info('Prefetching completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during prefetching: ' . $e->getMessage());
            Log::error('Prefetch command failed', ['exception' => $e->getMessage()]);
            return 1;
        }
    }
    
    /**
     * Prefetch popular movies
     */
    protected function prefetchPopular($pages)
    {
        $this->info('Prefetching popular movies...');
        $this->output->progressStart($pages);
        
        // Use common search terms to simulate popular movies
        $popularTerms = ['action', 'adventure', 'comedy', 'drama', 'sci-fi', 'thriller'];
        
        for ($page = 1; $page <= $pages; $page++) {
            // Randomly select a term for each page
            $term = $popularTerms[array_rand($popularTerms)];
            $this->movieService->searchMovies($term, $page);
            $this->output->progressAdvance();
            sleep(1); // Add delay to avoid rate limits
        }
        
        $this->output->progressFinish();
    }
    
    /**
     * Prefetch top rated movies
     */
    protected function prefetchTopRated($pages)
    {
        $this->info('Prefetching top rated movies...');
        $this->output->progressStart($pages);
        
        for ($page = 1; $page <= $pages; $page++) {
            $this->movieService->getTopRatedMovies($page);
            $this->output->progressAdvance();
            sleep(1); // Add delay to avoid rate limits
        }
        
        $this->output->progressFinish();
    }
}