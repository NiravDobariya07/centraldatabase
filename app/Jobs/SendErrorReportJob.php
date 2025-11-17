<?php

namespace App\Jobs;

use App\Mail\ErrorReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Constants\AppConstants;
use Exception;

class SendErrorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $errorDetails;

    /**
     * Create a new job instance.
     */
    public function __construct($errorDetails)
    {
        $this->errorDetails = $errorDetails;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            
            // Get the recipients from environment variable
            $recipients = explode(',', env('DEVELOPER_EMAILS')); // Separate email addresses by comma

            // Ensure to send to multiple recipients properly
            Mail::to($recipients)->send(new ErrorReportMail($this->errorDetails));
            customLog(AppConstants::LOG_CATEGORIES['EVENTS'], "✅ Error report email sent successfully.", [
                'recipients'   => $recipients,
                'errorDetails' => $this->errorDetails,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to send error report email.', [
                'error_message' => $e->getMessage(),
                'errorDetails'  => $this->errorDetails,
            ]);
        }
    }
}