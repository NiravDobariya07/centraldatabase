<?php

namespace App\Jobs;

use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Traits\LeadTrait;

class ProcessExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LeadTrait;

    public $exportId;
    public $timeout;

    protected string $queueName = 'default';
    protected array $worker = [];

    public function __construct($exportId)
    {
        $this->exportId = $exportId;
    }

    public function handle()
    {
        $this->queueName = $this->queue ?? 'default';
        $this->worker = collect(config('queue.workers'))->firstWhere('name', $this->queueName) ?? [
            'memory'  => 4096,
            'timeout' => 0,
        ];

        $this->timeout = $this->worker['timeout'];
        ini_set('memory_limit', "{$this->worker['memory']}M");
        ini_set('max_execution_time', $this->timeout);

        $logPrefix = "Export Id ({$this->exportId}) : [ProcessExport]";
        Log::channel('export_daily')->info("✅ {$logPrefix} - Queue: {$this->queueName}, Timeout: {$this->timeout}s, Memory Limit: {$this->worker['memory']}M");

        $export = Export::find($this->exportId);
        $modelType = $export->model_type ?? 'AllContact';

        // Route to the appropriate export method based on model_type
        switch ($modelType) {
            case 'ConsumerInsiteContact':
                $this->processConsumerInsiteContactExport($this->exportId);
                break;
            case 'TraContact':
                $this->processTraContactExport($this->exportId);
                break;
            case 'Offer':
                $this->processSiteTokenExport($this->exportId);
                break;
            case 'AllContact':
            default:
                $this->processLeadExport($this->exportId);
                break;
        }
    }

    public function failed(\Throwable $exception)
    {
        reportException($exception, "Error in ProcessExport", true, 'export_daily');
    }
}
