<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessListFlexData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $postData;

    /**
     * Create a new job instance.
     */
    public function __construct($postData)
    {
        $this->postData = $postData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Convert data to JSON (if array) or raw string
        $content = is_array($this->postData) ? json_encode($this->postData, JSON_PRETTY_PRINT) : (string)$this->postData;

        // Ensure the directory exists
        $directory = 'listflex_responses';
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        // Generate a unique filename with incremental order
        $order = $this->getNextFileOrder($directory);
        $fileName = "{$directory}/response_{$order}.txt";

        // Store the response data in a file
        Storage::disk('local')->put($fileName, $content);
    }

    /**
     * Helper function to get the next file order.
     */
    private function getNextFileOrder($directory)
    {
        // Get all files in the specified directory
        $files = Storage::disk('local')->files($directory);

        // Filter files that match "response_*.txt"
        $responseFiles = array_filter($files, function($file) {
            return preg_match('/response_\d+\.txt$/', $file);
        });

        // Extract numbers from the filenames and get the highest order number
        $orders = array_map(function($file) {
            preg_match('/response_(\d+)\.txt/', $file, $matches);
            return isset($matches[1]) ? (int)$matches[1] : 0;
        }, $responseFiles);

        // Determine the next order number (default to 1 if no files exist)
        return !empty($orders) ? max($orders) + 1 : 1;
    }
}
