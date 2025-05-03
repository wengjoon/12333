<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class GenerateFileBasedSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate-from-file {--limit=1000 : Maximum URLs per sitemap file} {--debug : Show additional debugging information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap from sitemap-urls.txt file, with up to 1000 URLs per sitemap';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting File-Based Sitemap Generation');
        
        $startTime = microtime(true);
        $urlsPerSitemap = $this->option('limit');
        $debug = $this->option('debug');
        
        // Set up paths
        $urlsFilePath = base_path('sitemap-urls.txt');
        $processedUrlsPath = base_path('urls-already.txt');
        
        if ($debug) {
            $this->info('Debug info:');
            $this->info('- Working directory: ' . getcwd());
            $this->info('- Base path: ' . base_path());
            $this->info('- Public path: ' . public_path());
            $this->info('- URLs file path: ' . $urlsFilePath);
            $this->info('- URLs already path: ' . $processedUrlsPath);
        }
        
        // Check if input file exists
        if (!File::exists($urlsFilePath)) {
            $this->error("File not found: sitemap-urls.txt");
            $this->info("Please create a file named 'sitemap-urls.txt' in the root directory with one URL per line.");
            return 1;
        }
        
        // Load URLs from file
        $this->info('Reading URLs from sitemap-urls.txt...');
        $urlsContent = File::get($urlsFilePath);
        $urls = array_filter(explode("\n", $urlsContent), function($url) {
            return !empty(trim($url));
        });
        
        // Remove any blank lines
        $urls = array_map('trim', $urls);
        $urls = array_filter($urls);
        
        $totalUrls = count($urls);
        $this->info("Found {$totalUrls} URLs to process");
        
        // Load already processed URLs if file exists
        $alreadyProcessedUrls = [];
        if (File::exists($processedUrlsPath)) {
            $alreadyProcessedContent = File::get($processedUrlsPath);
            $alreadyProcessedUrls = array_filter(explode("\n", $alreadyProcessedContent), function($url) {
                return !empty(trim($url));
            });
            $alreadyProcessedUrls = array_map('trim', $alreadyProcessedUrls);
            $this->info("Found " . count($alreadyProcessedUrls) . " already processed URLs");
            
            // Filter out already processed URLs
            $urls = array_diff($urls, $alreadyProcessedUrls);
            $this->info(count($urls) . " URLs remaining to process");
        }
        
        // If no URLs to process, exit
        if (empty($urls)) {
            $this->info("No new URLs to process. Sitemap is up to date.");
            return 0;
        }
        
        // Create sitemaps directory if it doesn't exist
        $sitemapsDir = public_path('sitemaps');
        if (!File::exists($sitemapsDir)) {
            File::makeDirectory($sitemapsDir, 0755, true);
            $this->info("Created sitemaps directory at {$sitemapsDir}");
        }
        
        // Calculate how many sitemap files we need
        $totalSitemaps = ceil(count($urls) / $urlsPerSitemap);
        $this->info("Will generate {$totalSitemaps} sitemap file(s)");
        
        // Create sitemap index content
        $sitemapIndex = '';
        if ($totalSitemaps > 1) {
            $sitemapIndex = $this->generateSitemapIndex($totalSitemaps);
        }
        
        // Initialize processed URLs array
        $processedUrls = [];
        
        // Generate sitemap files
        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();
        
        $urlChunks = array_chunk($urls, $urlsPerSitemap);
        foreach ($urlChunks as $index => $urlChunk) {
            $sitemapContent = $this->generateSitemap($urlChunk);
            $sitemapNumber = $index + 1;
            
            // Determine where to save the sitemap file
            if ($totalSitemaps > 1) {
                // Multiple sitemaps: save to sitemaps directory
                $filename = "sitemap-{$sitemapNumber}.xml";
                $path = public_path("sitemaps/{$filename}");
            } else {
                // Single sitemap: save to public root (this is the relevant case for your situation)
                $filename = "sitemap.xml";
                $path = public_path($filename);
            }
            
            // Save the sitemap file
            if ($debug) {
                $this->info("Writing sitemap to: {$path}");
            }
            
            try {
                // Ensure the directory exists
                $directory = dirname($path);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                
                // Write the file
                File::put($path, $sitemapContent);
                
                // Verify the file was created
                if (File::exists($path)) {
                    if ($debug) {
                        $this->info("Sitemap file successfully created at: {$path}");
                        $this->info("File size: " . File::size($path) . " bytes");
                    }
                } else {
                    $this->error("Failed to create sitemap file at: {$path}");
                }
            } catch (\Exception $e) {
                $this->error("Error writing sitemap file: " . $e->getMessage());
            }
            
            // Add URLs to processed list
            $processedUrls = array_merge($processedUrls, $urlChunk);
            
            // Update progress bar
            $bar->advance(count($urlChunk));
        }
        
        $bar->finish();
        $this->newLine();
        
        // Save sitemap index if we have multiple sitemaps
        if ($totalSitemaps > 1) {
            $indexPath = public_path('sitemap.xml');
            try {
                File::put($indexPath, $sitemapIndex);
                $this->info("Generated sitemap index at {$indexPath} linking to {$totalSitemaps} sitemaps");
                
                if ($debug && File::exists($indexPath)) {
                    $this->info("Sitemap index file size: " . File::size($indexPath) . " bytes");
                }
            } catch (\Exception $e) {
                $this->error("Error writing sitemap index: " . $e->getMessage());
            }
        } else {
            $this->info("Generated sitemap.xml with " . count($urls) . " URLs");
        }
        
        // Update the already processed URLs file
        $allProcessedUrls = array_unique(array_merge($alreadyProcessedUrls, $processedUrls));
        try {
            File::put($processedUrlsPath, implode("\n", $allProcessedUrls));
            $this->info("Updated urls-already.txt with " . count($allProcessedUrls) . " processed URLs");
        } catch (\Exception $e) {
            $this->error("Error updating urls-already.txt: " . $e->getMessage());
        }
        
        // List the files in the public directory to verify
        if ($debug) {
            $this->info('Files in public directory:');
            if (File::exists(public_path())) {
                foreach (File::files(public_path()) as $file) {
                    $this->info('- ' . $file->getFilename() . ' (' . $file->getSize() . ' bytes)');
                }
            } else {
                $this->info('Public directory not found or accessible');
            }
            
            $this->info('Files in sitemaps directory:');
            if (File::exists(public_path('sitemaps'))) {
                foreach (File::files(public_path('sitemaps')) as $file) {
                    $this->info('- ' . $file->getFilename() . ' (' . $file->getSize() . ' bytes)');
                }
            } else {
                $this->info('Sitemaps directory not found or accessible');
            }
        }
        
        // Display execution time
        $executionTime = round(microtime(true) - $startTime, 2);
        $this->info("Sitemap generation completed in {$executionTime} seconds");
        
        return 0;
    }
    
    /**
     * Generate sitemap XML content for a set of URLs
     *
     * @param array $urls
     * @return string
     */
    protected function generateSitemap(array $urls)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        $currentDate = Carbon::now()->toAtomString();
        
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>{$currentDate}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Generate sitemap index XML content
     *
     * @param int $totalSitemaps
     * @return string
     */
    protected function generateSitemapIndex($totalSitemaps)
    {
        $appUrl = config('app.url');
        $currentDate = Carbon::now()->toAtomString();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        for ($i = 1; $i <= $totalSitemaps; $i++) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$appUrl}/sitemaps/sitemap-{$i}.xml</loc>\n";
            $xml .= "    <lastmod>{$currentDate}</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
}