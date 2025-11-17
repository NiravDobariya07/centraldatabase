<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Export;
use App\Jobs\ProcessExport;
use Carbon\Carbon;
use App\Constants\AppConstants;
use App\Traits\LeadTrait;
use App\Traits\ExportJobDispatcher;
use Illuminate\Support\Facades\Log;

class RunScheduledExports extends Command
{
    use LeadTrait, ExportJobDispatcher;

    protected $signature = 'run:schedule-export';
    protected $description = 'Process scheduled exports that are due to run.';

    public function handle() {
        $logPrefix = '[Scheduled Export] :';

        $messageStartExecution = "⏳ {$logPrefix} Checking for scheduled exports... " . now()->format('Y-m-d H:i:s');
        $this->info($messageStartExecution);
        // Log::channel('export_daily')->info($messageStartExecution);

        $exports = Export::select('id', 'status', 'frequency')
            ->whereIn('frequency', [
                AppConstants::EXPORT_FREQUENCY_OPTIONS['DAILY'],
                AppConstants::EXPORT_FREQUENCY_OPTIONS['WEEKLY'],
                AppConstants::EXPORT_FREQUENCY_OPTIONS['MONTHLY']
            ])
            ->where('runing_status', AppConstants::EXPORT_RUNING_STATUS['PENDING'])
            ->where('status', AppConstants::EXPORT_STATUSES['ACTIVE'])
            ->where('next_run_at', '<=', now())
            ->get();

        if ($exports->isEmpty()) {

            $messageNoExportScheduledToRun = "✅ {$logPrefix} No exports scheduled to run.";
            $this->info($messageNoExportScheduledToRun);
            // Log::channel('export_daily')->info($messageNoExportScheduledToRun);
            return;
        }

        foreach ($exports as $export) {
            $totalExportableRecordsCount = $this->getExportableRecordCount($export->id);
            $this->dispatchExportJob($export->id, $totalExportableRecordsCount);
            $export->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SCHEDULED']]);

            $messageExportScheduled = "✅ {$logPrefix} Scheduled export [{$export->id}] dispatched.";
            $this->info($messageExportScheduled);
            Log::channel('export_daily')->info($messageExportScheduled);
        }

        $messageExecutionSuccess = "✅ {$logPrefix} Scheduled exports processed successfully.";
        $this->info($messageExecutionSuccess);
        Log::channel('export_daily')->info($messageExecutionSuccess);
    }
}