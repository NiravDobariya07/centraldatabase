<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{ AllContact, Export, ExportFile, SourceSite, CampaignListId };
use App\Constants\AppConstants;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Traits\LeadTrait;
use App\Traits\ExportJobDispatcher;
use App\Jobs\ProcessExport;
use Yajra\DataTables\Facades\DataTables;

class ExportController extends Controller
{
    use LeadTrait, ExportJobDispatcher;

    public function exportsListing(Request $request) {
        try {
            if ($request->ajax()) {
                $exports = Export::select('exports.*', 'users.name as user_name')
                    ->leftJoin('users', 'exports.user_id', '=', 'users.id'); // Join the users table

                return DataTables::of($exports)
                    ->editColumn('user_name', function ($export) {
                        return $export->user_name ?? 'N/A'; // Use joined column
                    })
                    ->editColumn('next_run_at', function ($export) {
                        return $export->next_run_at ? $export->next_run_at->format('Y-m-d H:i:s') : 'N/A';
                    })
                    ->editColumn('last_run_at', function ($export) {
                        return $export->last_run_at ? $export->last_run_at->format('Y-m-d H:i:s') : 'N/A';
                    })
                    ->addColumn('action', function ($export) {
                        return '
                                <a href="javascript:void(0);" data-id="'.$export->id.'" class="btn btn-sm btn-primary export-details">
                                    <i class="fa-solid fa-gear"></i>
                                </a>
                                <a href="javascript:void(0);" data-id="'.$export->id.'" class="btn btn-sm btn-primary export-specific-files">
                                    <i class="bx bx-history"></i>
                                </a>
                                <button class="btn btn-sm btn-danger delete-export" data-id="'.$export->id.'">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('pages.exports_listing')->with('activeTab', 'export-schedule');
        } catch (\Exception $e) {
            reportException($e, "Error fetching export schedule listings in exportsListing method");
            return response()->json(['error' => 'Something went wrong while fetching exports.'], 500);
        }
    }

    public function exportsFilesListing(Request $request) {
        try {
            if ($request->ajax()) {
                $selectedExportIds = $request->input('selected_export_ids', []);

                $exportFiles = ExportFile::select('export_files.*', 'users.name as user_name')
                    ->leftJoin('users', 'export_files.user_id', '=', 'users.id'); // Join users table

                if (!empty($selectedExportIds)) {
                    $exportFiles->whereIn('export_files.export_id', $selectedExportIds);
                }

                return DataTables::of($exportFiles)
                    ->addColumn('file_name_raw', function ($exportFile) {
                        // return '<a href="'.route('export.download.file', $export->id).'">' . e($export->file_name) . '</a>';
                        return '<a href="'.route('export.download.file', $exportFile->id).'" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" data-bs-html="true" title="" data-bs-original-title="<span>'.$exportFile->export->title.'</span>">' . e($exportFile->file_name) . '</a>';
                    })
                    ->addColumn('file_size_formatted', function ($exportFile) {
                        return $exportFile->file_size_mb . ' MB';
                    })
                    ->editColumn('created_at', function ($exportFile) {
                        return $exportFile->created_at ? $exportFile->created_at->diffForHumans() : 'N/A';
                    })
                    ->editColumn('expires_at', function ($exportFile) {
                        return $exportFile->expires_at ? $exportFile->expires_at->diffForHumans() : 'N/A';
                    })
                    ->addColumn('action', function ($exportFile) {
                        return '<a href="'.route('export.download.file', $exportFile->id).'" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button class="btn btn-sm btn-danger delete-export-file" data-id="'.$exportFile->id.'">
                                    <i class="fas fa-trash-alt"></i>
                                </button>';
                    })
                    ->rawColumns(['file_name_raw', 'action'])
                    ->make(true);
            }

            return view('pages.exports_listing')->with('activeTab', 'export-history');
        } catch (\Exception $e) {
            reportException($e, "Error fetching export files listings in exportsFilesListing method");
            return response()->json(['error' => 'Something went wrong while fetching exports files.'], 500);
        }
    }

    public function exportScheduleDetails(Request $request) {
        try {
            $exportId = $request->input('id');
            $exportData = Export::with(['user', 'exportFiles'])->findOrfail($exportId);

            $filterData = [];

            if (!empty($exportData->filters)) {
                $filterData = $exportData->filters;


                if (!empty($exportData->filters['source_site_id'])) {
                    if (!empty($exportData->filters['source_site_id'])) {
                        $filterData['source_sites'] = SourceSite::whereIn('id', (array) $exportData->filters['source_site_id'])
                            ->pluck('domain')
                            ->implode(', ');
                    }
                }

                if (!empty($exportData->filters['campaign_list_id'])) {
                    if (!empty($exportData->filters['campaign_list_id'])) {
                        $filterData['campaign_list'] = CampaignListId::whereIn('id', (array) $exportData->filters['campaign_list_id'])
                            ->pluck('list_id')
                            ->implode(', ');
                    }
                }
            }

            $exportData['filters_data'] = $filterData;

            if (!empty($exportData)) {
                return response()->json([
                    'success' => true,
                    'data' => $exportData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found.'
                ]);
            }
        } catch (\Exception $e) {
            reportException($e, "Error fetching export Details in exportScheduleDetails method");
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching export Details.'
            ]);
        }
    }

    public function exportScheduleStatusUpdate(Request $request) {
        try {
            $request->validate([
                'export_id' => 'required|exists:exports,id',
                'status'    => 'required|in:active,paused,stopped',
            ]);

            $export = Export::findOrFail($request->export_id);

            // Prevent unnecessary updates
            if ($export->status === $request->status) {
                return response()->json([
                    'message' => 'No changes made, export is already in this status'
                ], 200);
            }

            // Prevent updates if already stopped
            if ($export->status === 'stopped') {
                return response()->json([
                    'message' => 'Cannot update a stopped export'
                ], 403);
            }

            // Update status and running status
            $export->status = $request->status;

            if ($request->status === 'paused') {
                $export->next_run_at = null;
                $export->runing_status = 'paused';
            } elseif ($request->status === 'stopped') {
                $export->next_run_at = null;
                $export->runing_status = 'stopped';
            } elseif ($request->status === 'active' && $export->runing_status !== 'success') {
                $export->next_run_at = $export->calculateNextRun();
                $export->runing_status = 'pending';
            }

            $export->save();

            return response()->json([
                'message'      => 'Export schedule status updated successfully',
                'status'       => $export->status,
                'runing_status' => $export->runing_status,
                'next_run_at'  => $export->next_run_at,
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error updating export status in exportScheduleStatusUpdate method");
            return response()->json(['error' => 'Something went wrong while updating export status'], 500);
        }
    }

    public function scheduleLeadExport(Request $request) {
        try {
            $schedulLeadExportPosteData = $request->input('schedule_lead_export_data', []);

            // Define validation rules
            $rules = [
                'frequency'      => 'required|string|in:one_time,daily,weekly,monthly,custom',
                'export_type'    => 'required|string',
                'export_formats' => 'required|array|min:1',
                'export_columns' => 'required|array|min:1',
            ];

            // Define custom error messages
            $messages = [
                'frequency.required'      => 'The frequency field is required.',
                'export_type.required'    => 'The export type field is required.',
                'export_formats.required' => 'The export format field is required.',
                'export_formats.array'    => 'The export format must be an array.',
                'export_formats.min'      => 'The export format must contain at least one item.',
                'export_columns.required' => 'The export columns field is required.',
                'export_columns.array'    => 'The export columns must be an array.',
                'export_columns.min'      => 'The export columns must contain at least one item.',
            ];

            // Perform validation
            $validator = Validator::make($schedulLeadExportPosteData, $rules, $messages);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422); // 422 Unprocessable Entity
            }

            // Default title if not provided
            $defaultTitle = Carbon::now()->format('l, F j, Y');

            $additionalData = [];
            if(!empty($schedulLeadExportPosteData['sort_by_field_name']) && !empty($schedulLeadExportPosteData['sort_by_field_order'])) {
                $additionalData['sort_by'] = [
                    'field' => $schedulLeadExportPosteData['sort_by_field_name'],
                    'sorting_order' => $schedulLeadExportPosteData['sort_by_field_order']
                ];
            }

            if (!empty($schedulLeadExportPosteData['export_in_batches'])) {
                $additionalData['export_in_batches'] = 1;
            }

            $schedulLeadExportPayloadData = [
                'user_id'           => auth()->id(),
                'title'             => !empty(trim($schedulLeadExportPosteData['title'])) ? trim($schedulLeadExportPosteData['title']) : $defaultTitle,
                'description'       => !empty(trim($schedulLeadExportPosteData['description'])) ? trim($schedulLeadExportPosteData['description']) : null,
                'file_prefix'       => !empty(trim($schedulLeadExportPosteData['file_prefix'])) ? trim($schedulLeadExportPosteData['file_prefix']) : null,
                'export_formats'    => !empty($schedulLeadExportPosteData['export_formats']) ? $schedulLeadExportPosteData['export_formats'] : null,
                'columns'           => !empty($schedulLeadExportPosteData['export_columns']) ? $schedulLeadExportPosteData['export_columns'] : null,
                'frequency'         => !empty($schedulLeadExportPosteData['frequency']) ? $schedulLeadExportPosteData['frequency'] : null,
                'day_of_week'       => !empty($schedulLeadExportPosteData['day_of_week']) ? $schedulLeadExportPosteData['day_of_week'] : null,
                'day_of_month'      => !empty($schedulLeadExportPosteData['day_of_month']) ? $schedulLeadExportPosteData['day_of_month'] : null,
                'time'              => !empty($schedulLeadExportPosteData['time']) ? $schedulLeadExportPosteData['time'] : null,
                'additional_data'   => $additionalData,
                'runing_status'     => AppConstants::EXPORT_RUNING_STATUS['PENDING'],
                'status'            => AppConstants::EXPORT_STATUSES['ACTIVE']
            ];

            if (!empty($schedulLeadExportPosteData['export_type']) && $schedulLeadExportPosteData['export_type'] == 'export_filtered_data') {
                $schedulLeadExportPayloadData['filters'] = !empty($schedulLeadExportPosteData['filters']) ? $schedulLeadExportPosteData['filters'] : null;
            }

            // Insert into database
            $exportScheduledData = Export::create($schedulLeadExportPayloadData);

            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['next_run_at' => $exportScheduledData->calculateNextRun()]);

                if ($exportScheduledData->frequency == AppConstants::EXPORT_FREQUENCY_OPTIONS['ONE_TIME']) {
                    // Process export immediately for "one_time" frequency
                    $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SCHEDULED']]);

                    try {
                        // Process export synchronously
                        $this->processLeadExport($exportScheduledData->id);

                        // Small delay to ensure files are fully written to disk
                        usleep(500000); // 0.5 second delay

                        // Reload export to get generated files
                        $exportScheduledData->refresh();
                        $exportFiles = $exportScheduledData->exportFiles;

                        // Prepare file download links
                        $fileLinks = [];
                        foreach ($exportFiles as $file) {
                            // Verify file exists before adding to download list
                            if (Storage::disk('local')->exists($file->file_path)) {
                                $fileLinks[] = [
                                    'id' => $file->id,
                                    'name' => $file->file_name,
                                    'format' => $file->file_format,
                                    'download_url' => route('export.download.file', $file->id)
                                ];
                            }
                        }

                        // Update status to success
                        $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SUCCESS']]);

                        // Successful response with file links
                        return response()->json([
                            'message'       => 'Export completed successfully!',
                            'exportScheduledData' => $exportScheduledData,
                            'files' => $fileLinks,
                            'instant_export' => true
                        ]);
                    } catch (\Exception $e) {
                        // Update status to failed
                        $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['FAILED']]);
                        reportException($e, "Error processing instant export in scheduleLeadExport method");
                        return response()->json([
                            'message' => 'An error occurred while processing the export.',
                            'error'   => $e->getMessage(),
                        ], 500);
                    }
                }

                // Successful response for scheduled exports
                return response()->json([
                    'message'       => 'Export scheduled successfully!',
                    'exportScheduledData' => $exportScheduledData,
                    'instant_export' => false
                ]);
            } else {
                return response()->json([
                    'message'       => 'Export could not scheduled successfully!'
                ], 500);
            }
        } catch (\Exception $e) {
            reportException($e, "Error schedule Lead Export in scheduleLeadExport method");
            return response()->json([
                'message' => 'An error occurred while scheduling the export.',
                'error'   => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    public function scheduleConsumerInsiteContactExport(Request $request) {
        try {
            $scheduleContactExportPostData = $request->input('schedule_contact_export_data', []);

            // Define validation rules
            $rules = [
                'frequency'      => 'required|string|in:one_time,daily,weekly,monthly,custom',
                'export_type'    => 'required|string',
                'export_formats' => 'required|array|min:1',
                'export_columns' => 'required|array|min:1',
            ];

            // Define custom error messages
            $messages = [
                'frequency.required'      => 'The frequency field is required.',
                'export_type.required'    => 'The export type field is required.',
                'export_formats.required' => 'The export format field is required.',
                'export_formats.array'    => 'The export format must be an array.',
                'export_formats.min'      => 'The export format must contain at least one item.',
                'export_columns.required' => 'The export columns field is required.',
                'export_columns.array'    => 'The export columns must be an array.',
                'export_columns.min'      => 'The export columns must contain at least one item.',
            ];

            // Perform validation
            $validator = Validator::make($scheduleContactExportPostData, $rules, $messages);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422); // 422 Unprocessable Entity
            }

            // Default title if not provided
            $defaultTitle = Carbon::now()->format('l, F j, Y');

            $additionalData = [];
            if(!empty($scheduleContactExportPostData['sort_by_field_name']) && !empty($scheduleContactExportPostData['sort_by_field_order'])) {
                $additionalData['sort_by'] = [
                    'field' => $scheduleContactExportPostData['sort_by_field_name'],
                    'sorting_order' => $scheduleContactExportPostData['sort_by_field_order']
                ];
            }

            if (!empty($scheduleContactExportPostData['export_in_batches'])) {
                $additionalData['export_in_batches'] = 1;
            }

            $scheduleContactExportPayloadData = [
                'user_id'           => auth()->id(),
                'title'             => !empty(trim($scheduleContactExportPostData['title'])) ? trim($scheduleContactExportPostData['title']) : $defaultTitle,
                'description'       => !empty(trim($scheduleContactExportPostData['description'])) ? trim($scheduleContactExportPostData['description']) : null,
                'file_prefix'       => !empty(trim($scheduleContactExportPostData['file_prefix'])) ? trim($scheduleContactExportPostData['file_prefix']) : null,
                'export_formats'    => !empty($scheduleContactExportPostData['export_formats']) ? $scheduleContactExportPostData['export_formats'] : null,
                'columns'           => !empty($scheduleContactExportPostData['export_columns']) ? $scheduleContactExportPostData['export_columns'] : null,
                'frequency'         => !empty($scheduleContactExportPostData['frequency']) ? $scheduleContactExportPostData['frequency'] : null,
                'day_of_week'       => !empty($scheduleContactExportPostData['day_of_week']) ? $scheduleContactExportPostData['day_of_week'] : null,
                'day_of_month'      => !empty($scheduleContactExportPostData['day_of_month']) ? $scheduleContactExportPostData['day_of_month'] : null,
                'time'              => !empty($scheduleContactExportPostData['time']) ? $scheduleContactExportPostData['time'] : null,
                'additional_data'   => $additionalData,
                'runing_status'     => AppConstants::EXPORT_RUNING_STATUS['PENDING'],
                'status'            => AppConstants::EXPORT_STATUSES['ACTIVE']
            ];

            if (!empty($scheduleContactExportPostData['export_type']) && $scheduleContactExportPostData['export_type'] == 'export_filtered_data') {
                $scheduleContactExportPayloadData['filters'] = !empty($scheduleContactExportPostData['filters']) ? $scheduleContactExportPostData['filters'] : null;
            }

            // Insert into database
            $exportScheduledData = Export::create($scheduleContactExportPayloadData);

            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['next_run_at' => $exportScheduledData->calculateNextRun()]);

                if ($exportScheduledData->frequency == AppConstants::EXPORT_FREQUENCY_OPTIONS['ONE_TIME']) {
                    // Process export immediately for "one_time" frequency
                    $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SCHEDULED']]);

                    try {
                        // Process export synchronously
                        $this->processConsumerInsiteContactExport($exportScheduledData->id);

                        // Small delay to ensure files are fully written to disk
                        usleep(500000); // 0.5 second delay

                        // Reload export to get generated files
                        $exportScheduledData->refresh();
                        $exportFiles = $exportScheduledData->exportFiles;

                        // Prepare file download links
                        $fileLinks = [];
                        foreach ($exportFiles as $file) {
                            // Verify file exists before adding to download list
                            if (Storage::disk('local')->exists($file->file_path)) {
                                $fileLinks[] = [
                                    'id' => $file->id,
                                    'name' => $file->file_name,
                                    'format' => $file->file_format,
                                    'download_url' => route('export.download.file', $file->id)
                                ];
                            }
                        }

                        // Update status to success
                        $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SUCCESS']]);

                        // Successful response with file links
                        return response()->json([
                            'message'       => 'Export completed successfully!',
                            'exportScheduledData' => $exportScheduledData,
                            'files' => $fileLinks,
                            'instant_export' => true
                        ]);
                    } catch (\Exception $e) {
                        // Update status to failed
                        $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['FAILED']]);
                        reportException($e, "Error processing instant export in scheduleConsumerInsiteContactExport method");
                        return response()->json([
                            'message' => 'An error occurred while processing the export.',
                            'error'   => $e->getMessage(),
                        ], 500);
                    }
                }

                // Successful response for scheduled exports
                return response()->json([
                    'message'       => 'Export scheduled successfully!',
                    'exportScheduledData' => $exportScheduledData,
                    'instant_export' => false
                ]);
            } else {
                return response()->json([
                    'message'       => 'Export could not scheduled successfully!'
                ], 500);
            }
        } catch (\Exception $e) {
            reportException($e, "Error schedule Consumer Insite Contact Export in scheduleConsumerInsiteContactExport method");
            return response()->json([
                'message' => 'An error occurred while scheduling the export.',
                'error'   => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    public function scheduleTraContactExport(Request $request) {
        try {
            $scheduleContactExportPostData = $request->input('schedule_contact_export_data', []);

            // Define validation rules
            $rules = [
                'frequency'      => 'required|string|in:one_time,daily,weekly,monthly,custom',
                'export_type'    => 'required|string',
                'export_formats' => 'required|array|min:1',
                'export_columns' => 'required|array|min:1',
            ];

            // Define custom error messages
            $messages = [
                'frequency.required'      => 'The frequency field is required.',
                'export_type.required'    => 'The export type field is required.',
                'export_formats.required' => 'The export format field is required.',
                'export_formats.array'    => 'The export format must be an array.',
                'export_formats.min'      => 'The export format must contain at least one item.',
                'export_columns.required' => 'The export columns field is required.',
                'export_columns.array'    => 'The export columns must be an array.',
                'export_columns.min'      => 'The export columns must contain at least one item.',
            ];

            // Perform validation
            $validator = Validator::make($scheduleContactExportPostData, $rules, $messages);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422); // 422 Unprocessable Entity
            }

            // Default title if not provided
            $defaultTitle = Carbon::now()->format('l, F j, Y');

            $additionalData = [];
            if(!empty($scheduleContactExportPostData['sort_by_field_name']) && !empty($scheduleContactExportPostData['sort_by_field_order'])) {
                $additionalData['sort_by'] = [
                    'field' => $scheduleContactExportPostData['sort_by_field_name'],
                    'sorting_order' => $scheduleContactExportPostData['sort_by_field_order']
                ];
            }

            if (!empty($scheduleContactExportPostData['export_in_batches'])) {
                $additionalData['export_in_batches'] = 1;
            }

            $scheduleContactExportPayloadData = [
                'user_id'           => auth()->id(),
                'title'             => !empty(trim($scheduleContactExportPostData['title'])) ? trim($scheduleContactExportPostData['title']) : $defaultTitle,
                'description'       => !empty(trim($scheduleContactExportPostData['description'])) ? trim($scheduleContactExportPostData['description']) : null,
                'file_prefix'       => !empty(trim($scheduleContactExportPostData['file_prefix'])) ? trim($scheduleContactExportPostData['file_prefix']) : null,
                'export_formats'    => !empty($scheduleContactExportPostData['export_formats']) ? $scheduleContactExportPostData['export_formats'] : null,
                'columns'           => !empty($scheduleContactExportPostData['export_columns']) ? $scheduleContactExportPostData['export_columns'] : null,
                'frequency'         => !empty($scheduleContactExportPostData['frequency']) ? $scheduleContactExportPostData['frequency'] : null,
                'day_of_week'       => !empty($scheduleContactExportPostData['day_of_week']) ? $scheduleContactExportPostData['day_of_week'] : null,
                'day_of_month'      => !empty($scheduleContactExportPostData['day_of_month']) ? $scheduleContactExportPostData['day_of_month'] : null,
                'time'              => !empty($scheduleContactExportPostData['time']) ? $scheduleContactExportPostData['time'] : null,
                'additional_data'   => $additionalData,
                'runing_status'     => AppConstants::EXPORT_RUNING_STATUS['PENDING'],
                'status'            => AppConstants::EXPORT_STATUSES['ACTIVE']
            ];

            if (!empty($scheduleContactExportPostData['export_type']) && $scheduleContactExportPostData['export_type'] == 'export_filtered_data') {
                $scheduleContactExportPayloadData['filters'] = !empty($scheduleContactExportPostData['filters']) ? $scheduleContactExportPostData['filters'] : null;
            }

            // Insert into database
            $exportScheduledData = Export::create($scheduleContactExportPayloadData);

            if (!empty($exportScheduledData)) {
                $exportScheduledData->update(['next_run_at' => $exportScheduledData->calculateNextRun()]);

                if ($exportScheduledData->frequency == AppConstants::EXPORT_FREQUENCY_OPTIONS['ONE_TIME']) {
                    // Process export immediately for "one_time" frequency
                    $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SCHEDULED']]);

                    try {
                        // Process export synchronously
                        $this->processTraContactExport($exportScheduledData->id);

                        // Small delay to ensure files are fully written to disk
                        usleep(500000); // 0.5 second delay

                        // Reload export to get generated files
                        $exportScheduledData->refresh();
                        $exportFiles = $exportScheduledData->exportFiles;

                        // Prepare file download links
                        $fileLinks = [];
                        foreach ($exportFiles as $file) {
                            // Verify file exists before adding to download list
                            if (Storage::disk('local')->exists($file->file_path)) {
                                $fileLinks[] = [
                                    'id' => $file->id,
                                    'name' => $file->file_name,
                                    'format' => $file->file_format,
                                    'download_url' => route('export.download.file', $file->id)
                                ];
                            }
                        }

                        // Update status to success
                        $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['SUCCESS']]);

                        // Successful response with file links
                        return response()->json([
                            'message'       => 'Export completed successfully!',
                            'exportScheduledData' => $exportScheduledData,
                            'files' => $fileLinks,
                            'instant_export' => true
                        ]);
                    } catch (\Exception $e) {
                        // Update status to failed
                        $exportScheduledData->update(['runing_status' => AppConstants::EXPORT_RUNING_STATUS['FAILED']]);
                        reportException($e, "Error processing instant export in scheduleTraContactExport method");
                        return response()->json([
                            'message' => 'An error occurred while processing the export.',
                            'error'   => $e->getMessage(),
                        ], 500);
                    }
                }

                // Successful response for scheduled exports
                return response()->json([
                    'message'       => 'Export scheduled successfully!',
                    'exportScheduledData' => $exportScheduledData,
                    'instant_export' => false
                ]);
            } else {
                return response()->json([
                    'message'       => 'Export could not scheduled successfully!'
                ], 500);
            }
        } catch (\Exception $e) {
            reportException($e, "Error schedule TRA Contact Export in scheduleTraContactExport method");
            return response()->json([
                'message' => 'An error occurred while scheduling the export.',
                'error'   => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    public function downloadExportFile($exportFileId) {
        try {
            if (empty($exportFileId)) {
                return redirect()->back()->with('error', 'Export file not found or might be deleted');
            }

            $exportFileData = ExportFile::findOrFail($exportFileId);

            if (!empty($exportFileData->file_path)) {
                // Check if file exists in storage
                if (Storage::disk('local')->exists($exportFileData->file_path)) {
                    $filePath = Storage::disk('local')->path($exportFileData->file_path);

                    // Set proper headers for download
                    $headers = [
                        'Content-Type' => $exportFileData->file_format === 'csv'
                            ? 'text/csv'
                            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'Content-Disposition' => 'attachment; filename="' . $exportFileData->file_name . '"',
                        'Cache-Control' => 'no-cache, no-store, must-revalidate',
                        'Pragma' => 'no-cache',
                        'Expires' => '0',
                    ];

                    return response()->download($filePath, $exportFileData->file_name, $headers);
                }
            }

            return redirect()->back()->with('error', 'Export file not found or might be deleted');
        } catch (\Exception $e) {
            reportException($e, 'Download File Error');
            return redirect()->back()->with('error', 'An error occurred while downloading the file.');
        }
    }

    public function deleteExportFile($id) {
        try {
            $exportFile = ExportFile::find($id);

            if (!$exportFile) {
                return response()->json(['success' => false, 'message' => 'File not found'], 404);
            }

            // Delete the file from storage
            if (!empty($exportFile->file_path) && Storage::exists($exportFile->file_path)) {
                Storage::delete($exportFile->file_path);
            }

            // Delete the record from the database
            $exportFile->delete();

            return response()->json(['success' => true, 'message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            reportException($e, "Delete File Error");
            return response()->json(['success' => false, 'message' => 'An error occurred while deleting the file'], 500);
        }
    }

    public function exportScheduleOptionData(Request $request) {
        try {
            $data = Export::select(['id', 'title', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Export schedule details retrieved successfully.',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            reportException($e, "Export Schedule Option Data retrive Error");
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteExportSchedule(Request $request) {
        try {
            // Retrieve export schedule by ID
            $export_id = $request->input('export_id');
            $exportSchedule = Export::with(['exportFiles'])->find($export_id);

            // If the export schedule does not exist, return a 404 error
            if (!$exportSchedule) {
                return response()->json(['message' => 'Export schedule not found'], 404);
            }

            // Delete related export files
            $exportSchedule->exportFiles->each(function ($exportFile) {
                $exportFile->delete();
            });

            // Delete the export schedule itself
            $exportSchedule->delete();

            // Return success response
            return response()->json(['message' => 'Export schedule and related files deleted successfully'], 200);

        } catch (\Exception $e) {
            // Log the exception (assuming you have a custom log function)
            reportException($e, "Error deleting export schedule and related files");

            // Return error response with exception message
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the export schedule.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
