<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FailedJob;
use App\Models\FailedToDispatchLead;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ProcessLeadData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FailedOperationsController extends Controller
{
    public function getFailedOperationsList($type)
    {
        try {
            $activeTab = $type;

            // General log files
            $syatemLogDir = storage_path('logs');
            $syatemLogFiles = collect(File::files($syatemLogDir))
                ->sortByDesc(fn($file) => $file->getCTime())
                ->map(fn($file) => $file->getFilename())
                ->toArray();

            // System failed logs
            $systemFailedlogDir = storage_path('logs/errors');
            $systemFailedLogFiles = collect(File::files($systemFailedlogDir))
                ->sortByDesc(fn($file) => $file->getCTime())
                ->map(fn($file) => $file->getFilename())
                ->toArray();

            // Export logs
            $exportlogDir = storage_path('logs/exports');
            $exportLogFiles = collect(File::files($exportlogDir))
                ->sortByDesc(fn($file) => $file->getCTime())
                ->map(fn($file) => $file->getFilename())
                ->toArray();

            return view('pages.failed_operations')->with([
                'activeTab' => $activeTab,
                'syatemLogFiles' => $syatemLogFiles,
                'systemFailedLogFiles' => $systemFailedLogFiles,
                'exportLogFiles' => $exportLogFiles
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in getFailedOperationsList method while loading $type page");
            return redirect()->back()->with('error', "Failed to fetch $type list.");
        }
    }

    public function fetchFailedJobs(Request $request)
    {
        try {
            if ($request->ajax()) {
                $jobs = FailedJob::select('*');

                return DataTables::of($jobs)
                    ->addColumn('action', function ($job) {
                        return '<button class="btn btn-sm btn-primary view-job" data-id="' . $job->id . '">View</button>
                                <button class="btn btn-sm btn-warning retry-job" data-id="' . $job->id . '">Retry</button>
                                <button class="btn btn-sm btn-danger delete-job" data-id="' . $job->id . '">Delete</button>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
        } catch (\Exception $e) {
            reportException($e, "Error in fetchFailedJobs method while retrieving failed jobs data");
            return response()->json(['error' => 'Failed to fetch jobs.'], 500);
        }
    }

    public function fetchFailedJobDataById(Request $request)
    {
        try {
            $job = FailedJob::find($request->id);

            if (!$job) {
                return response()->json(['error' => 'Job not found!'], 404);
            }

            return response()->json($job);
        } catch (\Exception $e) {
            reportException($e, "Error in fetchFailedJobDataById method while retrieving job details");
            return response()->json(['error' => 'Failed to fetch job data.'], 500);
        }
    }

    public function retryFailedJob(Request $request)
    {
        try {
            $job = FailedJob::find($request->id);

            if ($job) {
                Artisan::call("queue:retry {$job->uuid}");
                return response()->json(['success' => 'Job retried successfully!', 'job' => $job]);
            }

            return response()->json(['error' => 'Job not found!'], 404);
        } catch (\Exception $e) {
            reportException($e, "Error in retryFailedJob method while retrying failed job");
            return response()->json(['error' => 'Failed to retry job.'], 500);
        }
    }

    public function deleteFailedJob(Request $request)
    {
        try {
            $job = FailedJob::find($request->id);

            if (!$job) {
                return response()->json(['error' => 'Job not found.'], 404);
            }

            $job->delete();

            return response()->json(['message' => 'Failed job deleted successfully.']);
        } catch (\Exception $e) {
            reportException($e, 'Error in deleteFailedJob method while deleting failed job');
            return response()->json(['error' => 'Unable to delete failed job.'], 500);
        }
    }

    public function fetchSystemLogs(Request $request)
    {
        ini_set('memory_limit', '2G');
        try {
            if (!$request->ajax()) {
                return response()->json(['error' => 'Invalid request.'], 400);
            }

            $logDir = storage_path('logs');
            $selectedLogFile = $request->log_file ?? null; // Get selected log file
            $logTypes = array_map('strtoupper', (array) $request->log_types ?? []); // Normalize log types

            if (!$selectedLogFile) {
                // Get the latest log file if no file is selected
                $latestLogFile = collect(File::files($logDir))
                    ->sortByDesc(fn($file) => $file->getCTime())
                    ->first();

                $selectedLogFile = $latestLogFile ? $latestLogFile->getFilename() : null;
            }

            if (!$selectedLogFile) {
                // return response()->json(['error' => 'No log file found.'], 404);
                return DataTables::of([])->make(true);
            }

            $logFilePath = $logDir . '/' . $selectedLogFile;
            if (!File::exists($logFilePath)) {
                // return response()->json(['error' => 'Log file not found.'], 404);
                return DataTables::of([])->make(true);
            }

            $logLines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = [];
            $currentTimestamp = null;
            $currentMessage = '';

            foreach ($logLines as $line) {
                // Match timestamps: [YYYY-MM-DD HH:MM:SS]
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    if ($currentTimestamp && !empty($currentMessage)) {
                        // Store previous log entry
                        $logs[] = [
                            'created_at' => $currentTimestamp,
                            'message' => trim($currentMessage)
                        ];
                    }

                    // Start a new log entry
                    $currentTimestamp = $matches[1];
                    $currentMessage = $line;
                } else {
                    // Append continuation lines to the current message
                    $currentMessage .= "\n" . $line;
                }
            }

            // Push the last log entry
            if ($currentTimestamp && !empty($currentMessage)) {
                $logs[] = [
                    'created_at' => $currentTimestamp,
                    'message' => trim($currentMessage)
                ];
            }

            // Filter logs by type (case-insensitive)
            if (!empty($logTypes)) {
                $logs = array_filter($logs, function ($log) use ($logTypes) {
                    foreach ($logTypes as $type) {
                        if (stripos($log['message'], $type) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            return DataTables::of($logs)->make(true);

        } catch (\Exception $e) {
            reportException($e, "Error in fetchFailedLogs method while retrieving failed logs");
            return response()->json(['error' => 'Failed to fetch logs.'], 500);
        }
    }

    public function fetchSystemFailedLogs(Request $request)
    {
        ini_set('memory_limit', '2G');

        try {

            if (!$request->ajax()) {
                return response()->json(['error' => 'Invalid request.'], 400);
            }

            $logDir = storage_path('logs/errors');
            $selectedLogFile = $request->log_file ?? null;
            $logTypes = array_map('strtoupper', (array) $request->log_types ?? []);

            if (!$selectedLogFile) {
                $latestLogFile = collect(File::files($logDir))
                    ->sortByDesc(fn($file) => $file->getCTime())
                    ->first();
                $selectedLogFile = $latestLogFile ? $latestLogFile->getFilename() : null;
            }

            if (!$selectedLogFile || !File::exists($logDir . '/' . $selectedLogFile)) {
                return DataTables::of([])->make(true);
            }

            $logLines = file($logDir . '/' . $selectedLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = [];
            $currentTimestamp = null;
            $currentMessage = '';

            foreach ($logLines as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    if ($currentTimestamp && !empty($currentMessage)) {
                        $logs[] = [
                            'created_at' => $currentTimestamp,
                            'message' => trim($currentMessage),
                        ];
                    }

                    $currentTimestamp = $matches[1];
                    $currentMessage = $line;
                } else {
                    $currentMessage .= "\n" . $line;
                }
            }

            if ($currentTimestamp && !empty($currentMessage)) {
                $logs[] = [
                    'created_at' => $currentTimestamp,
                    'message' => trim($currentMessage),
                ];
            }

            if (!empty($logTypes)) {
                $logs = array_filter($logs, function ($log) use ($logTypes) {
                    foreach ($logTypes as $type) {
                        if (stripos($log['message'], $type) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            return DataTables::of(array_values($logs))->make(true);
        } catch (\Exception $e) {
            reportException($e, 'Error in fetchSystemLogs method');
            return response()->json(['message' => 'Failed to fetch system failed logs.'], 500);
        }
    }

    public function fetchExportLogs(Request $request)
    {
        ini_set('memory_limit', '2G');

        try {
            if (!$request->ajax()) {
                return response()->json(['error' => 'Invalid request.'], 400);
            }

            $logDir = storage_path('logs/exports');
            $selectedLogFile = $request->log_file ?? null;
            $logTypes = array_map('strtoupper', (array) $request->log_types ?? []);

            if (!$selectedLogFile) {
                $latestLogFile = collect(File::files($logDir))
                    ->sortByDesc(fn($file) => $file->getCTime())
                    ->first();
                $selectedLogFile = $latestLogFile ? $latestLogFile->getFilename() : null;
            }

            if (!$selectedLogFile || !File::exists($logDir . '/' . $selectedLogFile)) {
                return DataTables::of([])->make(true);
            }

            $logLines = file($logDir . '/' . $selectedLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs = [];
            $currentTimestamp = null;
            $currentMessage = '';

            foreach ($logLines as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    if ($currentTimestamp && !empty($currentMessage)) {
                        $logs[] = [
                            'created_at' => $currentTimestamp,
                            'message' => trim($currentMessage),
                        ];
                    }

                    $currentTimestamp = $matches[1];
                    $currentMessage = $line;
                } else {
                    $currentMessage .= "\n" . $line;
                }
            }

            if ($currentTimestamp && !empty($currentMessage)) {
                $logs[] = [
                    'created_at' => $currentTimestamp,
                    'message' => trim($currentMessage),
                ];
            }

            if (!empty($logTypes)) {
                $logs = array_filter($logs, function ($log) use ($logTypes) {
                    foreach ($logTypes as $type) {
                        if (stripos($log['message'], $type) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            return DataTables::of(array_values($logs))->make(true);
        } catch (\Exception $e) {
            reportException($e, 'Error in fetchExportLogs method');
            return response()->json(['error' => 'Failed to fetch export logs.'], 500);
        }
    }

    public function fetchFailedDispatchLeads(Request $request) {
        try {
            if ($request->ajax()) {
                $leads = FailedToDispatchLead::select('*');

                return DataTables::of($leads)
                    ->addColumn('action', function ($lead) {
                        return '<button class="btn btn-sm btn-primary view-failed-to-dispatch-lead" data-id="' . $lead->id . '">View</button>
                                <button class="btn btn-sm btn-warning retry-failed-to-dispatch-lead" data-id="' . $lead->id . '">Retry</button>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
        } catch (\Exception $e) {
            reportException($e, "Error in fetchFailedDispatchLeads method while retrieving data");
            return response()->json(['error' => 'Failed to fetch dispatch jobs.'], 500);
        }
    }

    /**
     * Fetch failed dispatch lead data by ID.
     */
    public function fetchFailedDispatchLeadDataById(Request $request)
    {
        try {
            $lead = FailedToDispatchLead::find($request->id);

            if (!$lead) {
                return response()->json(['error' => 'Lead not found!'], 404);
            }

            return response()->json($lead);
        } catch (\Exception $e) {
            reportException($e, "Error in fetchFailedDispatchLeadDataById method while retrieving lead details");
            return response()->json(['error' => 'Failed to fetch lead data.'], 500);
        }
    }

    /**
     * Retry processing of a failed dispatch lead by ID.
     */
    public function retryFailedDispatchLeadDataById(Request $request)
    {
        DB::beginTransaction();
        try {
            $lead = FailedToDispatchLead::find($request->id);

            if ($lead) {
                // Dispatch the job with the stored payload
                ProcessLeadData::dispatch($lead->payload)->onQueue(config('queue.queues.default_queue'));

                // Delete the lead record upon successful dispatch
                $lead->delete();

                DB::commit();
                return response()->json(['success' => 'Lead retried successfully!', 'lead' => $lead]);
            }

            return response()->json(['error' => 'Lead not found!'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            reportException($e, "Error in retryFailedDispatchLeadDataById method while retrying lead dispatch");
            return response()->json(['error' => 'Failed to retry lead dispatch.'], 500);
        }
    }

    /**
     * Download log file by type and filename.
     * Valid types: system-logs, system-failed-logs, export-logs
     */
    public function downloadLogFile($type, $filename)
    {
        try {
            $validTypes = [
                'system-logs' => storage_path('logs'),
                'system-failed-logs' => storage_path('logs/errors'),
                'export-logs' => storage_path('logs/exports'),
            ];

            if (!array_key_exists($type, $validTypes)) {
                return back()->with('error', "Invalid log type: $type");
            }

            $dir = $validTypes[$type];
            $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

            if (!File::exists($filePath)) {
                return back()->with('error', 'Log file not found.');
            }

            // Prevent caching by setting appropriate headers
            $headers = [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ];

            $user = auth()->user();
            Log::channel('log_activity')->info("📥 [{$user->name}] (ID: {$user->id}) downloaded log '{$filePath}'");

            return response()->download($filePath, $filename, $headers);

        } catch (\Exception $e) {
            reportException($e, "Error in downloadLogFile for type: {$type}, filename: {$filename}");
            return back()->with('error', 'Something went wrong while downloading the log file.');
        }
    }

    /**
     * Delete log file by type and filename.
     * Redirects with status after delete attempt.
     */
    public function deleteLogFile($type, $filename)
    {
        try {
            $validTypes = [
                'system-logs' => storage_path('logs'),
                'system-failed-logs' => storage_path('logs/errors'),
                'export-logs' => storage_path('logs/exports'),
            ];

            if (!array_key_exists($type, $validTypes)) {
                return back()->with('error', "Invalid log type: $type");
            }

            $dir = $validTypes[$type];

            $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

            if (!File::exists($filePath)) {
                return back()->with('error', 'Log file not found.');
            }

            File::delete($filePath);

            $user = auth()->user();
            Log::channel('log_activity')->info("🗑️ [{$user->name}] (ID: {$user->id}) deleted log '{$filePath}'");

            return redirect()->route('failed-operations.list', ['type' => $type])
                ->with('success', 'Log file deleted successfully.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'An error occurred while deleting the log file.');
        }
    }
}
