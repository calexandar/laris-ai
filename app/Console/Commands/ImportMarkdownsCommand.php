<?php

namespace App\Console\Commands;

use App\Models\Document;
use Laravel\Ai\Embeddings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportMarkdownsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markdown:import {path? : Path to markdown files directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import markdown files into knowledge base and generate embeddings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path') ?? resource_path('markdowns');
        
        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return Command::FAILURE;
        }

        $this->info("Importing markdown files from: {$path}");
        
        $files = File::glob($path.'/*.md');
        
        if (empty($files)) {
            $this->info('No markdown files found.');
            return Command::SUCCESS;
        }

        $this->withProgressBar($files, function ($file) {
            $this->importMarkdownFile($file);
        });

        $this->newLine();
        $this->info('Successfully imported '.count($files).' markdown files.');
        
        return Command::SUCCESS;
    }

    private function importMarkdownFile(string $filePath): void
    {
        $content = File::get($filePath);
        $title = $this->extractTitle($content, basename($filePath));
        $relativePath = str_replace(resource_path('markdowns').'/', '', $filePath);

        // Generate embeddings  
        try {
            $embedding = Embeddings::for([
                'content' => $content])->generate();
            $embeddingArray = is_array($embedding) ? $embedding : (method_exists($embedding, 'toArray') ? $embedding->toArray() : (array) $embedding);
        } catch (\Exception $e) {
            Log::error('Failed to generate embeddings', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            $embeddingArray = null;
        }

        // Create or update document
        Document::updateOrCreate(
            ['file_path' => $relativePath],
            [
                'title' => $title,
                'content' => $content,
                'embedding' => $embeddingArray,
            ]
        );
    }

    private function extractTitle($content, $filename): string
    {
        // Try to extract title from first H1
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        // Fallback to filename without extension
        return ucfirst(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
    }
}