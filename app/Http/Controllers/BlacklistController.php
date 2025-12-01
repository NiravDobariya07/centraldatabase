<?php

namespace App\Http\Controllers;

use App\Models\BlacklistListing;
use App\Models\ExportFile;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class BlacklistController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $blacklist = BlacklistListing::select(['*'])->orderBy('created_at', 'desc');

                // Column-specific filter
                if (!empty($request->filter_column) && !empty($request->search_value)) {
                    $column = $request->filter_column;
                    $searchTerm = '%' . $request->search_value . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'email' => 'email',
                        'response' => 'response',
                        'source' => 'source',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $blacklist->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($request->search_value)) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $request->search_value . '%';
                    $blacklist->where(function ($query) use ($searchTerm) {
                        $query->where('email', 'LIKE', $searchTerm)
                            ->orWhere('response', 'LIKE', $searchTerm)
                            ->orWhere('source', 'LIKE', $searchTerm)
                            ->orWhere('source_type', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates
                if (!empty($request->start_date) && !empty($request->end_date)) {
                    // Both dates provided - validate and filter between them
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::parse($request->end_date)->endOfDay();

                    // Validate that end date is not before start date
                    if ($endDate->lt($startDate)) {
                        return response()->json([
                            'error' => 'End Date cannot be before Start Date'
                        ], 422);
                    }

                    $blacklist->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->start_date)) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $blacklist->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->end_date)) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    $blacklist->where('created_at', '<=', $endDate);
                }

                return DataTables::of($blacklist)
                ->addColumn('source', function ($blacklistItem) {
                    // Combine source_type and source, but avoid duplicates
                    $sourceType = trim($blacklistItem->source_type ?? '');
                    $source = trim($blacklistItem->source ?? '');

                    if (empty($sourceType) && empty($source)) {
                        return 'N/A';
                    }

                    // If both are the same, show only once
                    if ($sourceType === $source) {
                        return $sourceType ?: $source;
                    }

                    // If one is empty, show the other
                    if (empty($sourceType)) {
                        return $source;
                    }
                    if (empty($source)) {
                        return $sourceType;
                    }

                    // If different, combine them
                    return $sourceType . ' - ' . $source;
                })
                ->addColumn('action', function ($blacklistItem) {
                    return '<a href="'.route('blacklist.show', $blacklistItem->id).'" class="btn btn-sm btn-primary">View</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $blacklistListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $blacklistListingSetting && isset($blacklistListingSetting->blacklist_fields)
                ? json_decode($blacklistListingSetting->blacklist_fields, true)
                : [];
            $defaultFields = [
                "email" => "Email",
                "response" => "Response",
                "source" => "Source",
                "created_at" => "Created Date"
            ];

            return view('pages.blacklist', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching blacklist");
            return redirect()->back()->with('error', 'Failed to load blacklist listing.');
        }
    }

    public function show($id)
    {
        try {
            $blacklistItem = BlacklistListing::findOrFail($id);

            $blacklistDetails = [
                [
                    'Basic Information' => [
                        'Email' => $blacklistItem->email,
                        'Response' => $blacklistItem->response,
                        'Source' => $this->formatSourceColumn($blacklistItem->source_type, $blacklistItem->source)
                    ],
                ],
                [
                    'Metadata' => [
                        'Created At' => $blacklistItem->created_at,
                        'Updated At' => $blacklistItem->updated_at
                    ]
                ]
            ];

            return view('pages.blacklist_show', compact('blacklistDetails'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving blacklist details");
            return redirect()->back()->with('error', 'Unable to retrieve blacklist details.');
        }
    }

    public function saveBlacklistFieldSetting(Request $request) {
        try {
            $fields = $request->input('fields');
            $jsonData = json_encode($fields);
            $userId = auth()->id();

            if($jsonData) {
                Setting::updateOrCreate(
                    ['user_id' => $userId],
                    ['blacklist_fields' => $jsonData]
                );
            } else {
                $this->resetBlacklistFieldSetting();
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveBlacklistFieldSetting method while saving blacklist field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetBlacklistFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['blacklist_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetBlacklistFieldSetting method while resetting blacklist field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    /**
     * Format source column by combining source_type and source, avoiding duplicates
     */
    private function formatSourceColumn($sourceType, $source)
    {
        $sourceType = trim($sourceType ?? '');
        $source = trim($source ?? '');

        if (empty($sourceType) && empty($source)) {
            return 'N/A';
        }

        // If both are the same, show only once
        if ($sourceType === $source) {
            return $sourceType ?: $source;
        }

        // If one is empty, show the other
        if (empty($sourceType)) {
            return $source;
        }
        if (empty($source)) {
            return $sourceType;
        }

        // If different, combine them
        return $sourceType . ' - ' . $source;
    }
}

