<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessLeadData;
use Illuminate\Support\Facades\DB;
use App\Models\Lead;
use App\Models\SourceSite;
use App\Models\FailedToDispatchLead;
use App\Repositories\{
    LeadRepository,
    CampaignListIdRepository,
    SourceSiteRepository
};

class ListFlexController extends Controller
{
    private $leadRepository;

    public function __construct(LeadRepository $leadRepository) {
        $this->leadRepository = $leadRepository;
    }

    public function getListflexData(Request $request)
    {
        // Set max execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        $requestData = sanitizeDataForUtf8($request->all());

        try {
            // Dispatch the job to handle the lead processing and storing
            ProcessLeadData::dispatch($requestData)
                ->onQueue(config('queue.queues.default_queue'));

            return response()->json([
                'status'  => 'success',
                'message' => 'The lead has been successfully added to the queue for processing.',
            ], 200);

        } catch (\Exception $e) {
            // DB is up, so we can safely log the failed request
            reportException($e, "🚫 Adding Lead data to queue", false);

            // Check if DB is down before trying to store anything
            try {
                DB::connection()->getPdo(); // If this fails, we know DB is down
            } catch (\Exception $dbConnectionException) {
                reportException($dbConnectionException, "🚫 Database connection failed — unable to store failed dispatch data into FailedToDispatchLead.", false);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'The database is temporarily down. Please try again once it’s up. Our developer team has already been notified about this incident. Kindly stop sending further requests temporarily, and resend the data only after confirming the issue resolution with the developer team to reduce manual efforts.',
                ], 503);
            }

            // Log the failed lead data
            FailedToDispatchLead::create([
                'payload'        => $requestData,
                'error_message'  => $e->getMessage(),
                'exception_code' => $e->getCode(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'stack_trace'    => $e->getTraceAsString(),
                'client_ip'      => $request->ip(),
                'user_agent'     => $request->header('User-Agent'),
                'request_url'    => $request->fullUrl(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'We’re experiencing a temporary issue. Your request has been safely recorded and will be handled shortly. Our team has been notified. Kindly stop sending further requests temporarily, and only proceed after confirming the issue resolution with the developer team to reduce manual efforts.',
            ], 503);
        }
    }
}