<?php

namespace App\Http\Controllers;

use App\Models\FlmApiLead;
use App\Models\ExportFile;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class WhiteCollarLeadsController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $leads = FlmApiLead::select(['*'])->orderBy('created_at', 'desc');

                // Column-specific filter
                if (!empty($request->filter_column) && !empty($request->search_value)) {
                    $column = $request->filter_column;
                    $searchTerm = '%' . $request->search_value . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'email_address' => 'email_address',
                        'lead_timestamp' => 'lead_timestamp',
                        'payout_paid' => 'payout_paid',
                        'result' => 'result',
                        'lead_id' => 'lead_id',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $leads->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($request->search_value)) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $request->search_value . '%';
                    $leads->where(function ($query) use ($searchTerm) {
                        $query->where('lead_id', 'LIKE', $searchTerm)
                            ->orWhere('email_address', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('lead_timestamp', 'LIKE', $searchTerm)
                            ->orWhere('payout_paid', 'LIKE', $searchTerm)
                            ->orWhere('result', 'LIKE', $searchTerm);
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

                    $leads->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->start_date)) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $leads->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->end_date)) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    $leads->where('created_at', '<=', $endDate);
                }

                return DataTables::of($leads)
                ->addColumn('action', function ($lead) {
                    return '<a href="'.route('whitecollar-leads.show', $lead->id).'" class="btn btn-sm btn-primary">View</a>';
                })
                ->rawColumns(['action', 'fetch_paid_response'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $leadListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $leadListingSetting && isset($leadListingSetting->whitecollar_lead_fields)
                ? json_decode($leadListingSetting->whitecollar_lead_fields, true)
                : [];
            $defaultFields = [
                "first_name" => "First Name",
                "email_address" => "Email",
                "lead_id" => "LeadID",
                "fetch_paid_response" => "Cake response",
                "payout_paid" => "Price Paid",
                "result" => "Result",
                "resultid" => "ResultID",
                "created_at" => "Created at"
            ];

            return view('pages.whitecollar_leads', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching whitecollar leads");
            return redirect()->back()->with('error', 'Failed to load whitecollar leads listing.');
        }
    }

    public function show($id)
    {
        try {
            $lead = FlmApiLead::findOrFail($id);

            $leadDetails = [
                [
                    'Basic Lead Information' => [
                        'First Name' => $lead->first_name,
                        'Email Address' => $lead->email_address,
                        'Lead Timestamp' => $lead->lead_timestamp,
                        'Lead ID' => $lead->lead_id,
                    ],
                    'Payment Information' => [
                        'Payout Paid' => $lead->payout_paid,
                        'Cake Response' => $lead->fetch_paid_response, // This will be replaced with icon button in view
                    ],
                ],
                [
                    'Email Validation' => [
                        'Result' => $lead->result,
                        'Result ID' => $lead->resultid,
                        'Response' => $lead->response
                    ],
                    'Status Flags' => [
                        'Is Email Duplicate' => $lead->is_email_duplicate ? 'Yes' : 'No',
                        'EOAPI Success' => $lead->eoapi_success ? 'Yes' : 'No'
                    ],
                ],
                [
                    'Ongage Information' => [
                        'Is Ongage' => $lead->is_ongage ? 'Yes' : 'No',
                        'Ongage Response' => $lead->ongage_response,
                        'Ongage At' => $lead->ongage_at
                    ],
                    'Metadata' => [
                        'Created At' => $lead->created_at,
                    ]
                ]
            ];

            return view('pages.whitecollar_lead_show', compact('leadDetails', 'lead'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving whitecollar lead details");
            return redirect()->back()->with('error', 'Unable to retrieve whitecollar lead details.');
        }
    }

    public function saveWhiteCollarLeadFieldSetting(Request $request) {
        try {
            $fields = $request->input('fields');
            $jsonData = json_encode($fields);
            $userId = auth()->id();

            if($jsonData) {
                Setting::updateOrCreate(
                    ['user_id' => $userId],
                    ['whitecollar_lead_fields' => $jsonData]
                );
            } else {
                $this->resetWhiteCollarLeadFieldSetting();
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveWhiteCollarLeadFieldSetting method while saving whitecollar lead field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetWhiteCollarLeadFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['whitecollar_lead_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetWhiteCollarLeadFieldSetting method while resetting whitecollar lead field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    public function getTestLeads(Request $request)
    {
        try {
            // Find leads where first_name contains test
            $testLeads = FlmApiLead::where('first_name', 'LIKE', '%test%')
                ->orWhere('email_address', 'LIKE', '%test%')
                ->select('id', 'first_name', 'email_address', 'lead_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get date range
            $dateRange = null;
            if ($testLeads->count() > 0) {
                $minDate = $testLeads->min('created_at');
                $maxDate = $testLeads->max('created_at');
                if ($minDate && $maxDate) {
                    $dateRange = Carbon::parse($minDate)->format('Y-m-d H:i:s') . ' To ' . Carbon::parse($maxDate)->format('Y-m-d H:i:s');
                }
            }

            // Format the data
            $formattedLeads = $testLeads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'first_name' => $lead->first_name,
                    'email_address' => $lead->email_address,
                    'lead_id' => $lead->lead_id,
                    'created_at' => $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : 'N/A'
                ];
            });

            return response()->json([
                'success' => true,
                'total_count' => $testLeads->count(),
                'display_count' => $testLeads->count(),
                'date_range' => $dateRange,
                'leads' => $formattedLeads
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in getTestLeads method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test leads.'
            ], 500);
        }
    }

    public function deleteTestLeads(Request $request)
    {
        try {
            // Find and delete leads where first_name or email_address contains test
            $deletedCount = FlmApiLead::where('first_name', 'LIKE', '%test%')
                ->orWhere('email_address', 'LIKE', '%test%')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} test lead(s).",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in deleteTestLeads method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test leads.'
            ], 500);
        }
    }
}

