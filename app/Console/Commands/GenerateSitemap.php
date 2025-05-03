<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.xml file and save it to public folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');
        
        try {
            // Create an instance of the SitemapController
            $controller = app()->make(SitemapController::class);
            
            // Get the generateSitemap method using reflection (since it's protected)
            $reflectionMethod = new \ReflectionMethod($controller, 'generateSitemap');
            $reflectionMethod->setAccessible(true);
            
            // Generate the sitemap content
            $sitemap = $reflectionMethod->invoke($controller);
            
            // Save sitemap to public folder
            File::put(public_path('sitemap.xml'), $sitemap);
            
            $this->info('Sitemap generated successfully at public/sitemap.xml');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error generating sitemap: ' . $e->getMessage());
            return 1;
        }
    }
}