<?php

namespace App\Traits;

use App\Jobs\ProcessExport;
use Illuminate\Support\Facades\Log;

trait ExportJobDispatcher
{
    /**
     * Dispatches the export job to the appropriate queue based on record count and priority.
     *
     * @param int $exportId     The ID of the export being processed.
     * @param int $recordCount  Total number of records to be exported.
     * @param int $priority     Priority level of the export (default is 5).
     */
    public function dispatchExportJob(int $exportId, int $recordCount, int $priority = 5): void
    {
        // Prefix for consistent logging
        $logPrefix = "Export Id ({$exportId}) : [Export Dispatcher] ";

        // Determine appropriate queue based on record count
        $queue = $this->determineExportQueue($recordCount);

        // Log the dispatch action to the export_daily log channel
        Log::channel('export_daily')->info("🚀 {$logPrefix} Dispatching export job for Export ID: {$exportId}, Record Count: {$recordCount}, Priority: {$priority}, Queue: {$queue}");

        // Dispatch the export job to the resolved queue
        ProcessExport::dispatch($exportId, $priority)->onQueue($queue);
    }

    /**
     * Determines the appropriate queue name based on the number of records.
     *
     * @param int $count  Total number of records to be exported.
     * @return string     Queue name from configuration.
     */
    protected function determineExportQueue(int $count): string
    {
        return match (true) {
            $count <= 1_000         => config('queue.queues.tiny_export'),            // Up to 1K records
            $count <= 10_000        => config('queue.queues.small_export'),           // Up to 10K records
            $count <= 100_000       => config('queue.queues.medium_export'),          // Up to 100K records
            $count <= 500_000       => config('queue.queues.large_export'),           // Up to 500K records
            $count <= 1_000_000     => config('queue.queues.very_large_export'),      // Up to 1M records
            $count <= 5_000_000     => config('queue.queues.extreme_large_export'),   // Up to 5M records
            default                 => config('queue.queues.massive_export'),         // Over 5M records
        };
    }
}
