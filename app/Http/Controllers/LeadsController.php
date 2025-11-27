<?php

namespace App\Http\Controllers;

use App\Models\AllContact;
use App\Models\ExportFile;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class LeadsController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $leads = AllContact::select(['*']);

                // Column-specific filter
                if (!empty($request->filter_column) && !empty($request->search_value)) {
                    $column = $request->filter_column;
                    $searchTerm = '%' . $request->search_value . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'email_domain' => 'email_domain',
                        'phone' => 'phone',
                        'aff_id' => 'aff_id',
                        'sub_id' => 'sub_id',
                        'journya' => 'journya',
                        'cake_leadid' => 'cake_leadid',
                        'optin_domain' => 'optin_domain',
                        'domain_abt' => 'domain_abt',
                        'trusted_form' => 'trusted_form',
                        'ip_address' => 'ip_address',
                        'esp' => 'esp',
                        'result' => 'result',
                        'offer_id' => 'offer_id',
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
                        $query->where('cake_leadid', 'LIKE', $searchTerm)
                            ->orWhere('email', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('email_domain', 'LIKE', $searchTerm)
                            ->orWhere('optin_domain', 'LIKE', $searchTerm)
                            ->orWhere('aff_id', 'LIKE', $searchTerm)
                            ->orWhere('sub_id', 'LIKE', $searchTerm)
                            ->orWhere('journya', 'LIKE', $searchTerm)
                            ->orWhere('trusted_form', 'LIKE', $searchTerm);
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
                ->addColumn('full_name', function ($lead) {
                    return $lead->first_name . ' ' . $lead->last_name;
                })
                ->orderColumn('full_name', function ($query, $order) {
                    $query->orderBy('first_name', $order)->orderBy('last_name', $order);
                })
                ->addColumn('action', function ($lead) {
                    return '<a href="'.route('leads.show', $lead->id).'" class="btn btn-sm btn-primary">View</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $leadListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $leadListingSetting ? json_decode($leadListingSetting->lead_fields, true) : [];
            $defaultFields = [
                "full_name" => "Name",
                "email" => "Email",
                "phone" => "Phone",
                "email_domain" => "Email Domain",
                "optin_domain" => "Optin Domain",
                "domain_abt" => "Domain ABT",
                "aff_id" => "Affiliate ID",
                "sub_id" => "Sub ID",
                "cake_leadid" => "Cake Lead ID",
                "result" => "Result",
                "resultid" => "Result ID",
                "response" => "Response",
                "journya" => "Journya",
                "trusted_form" => "Trusted Form",
                "ip_address" => "IP Address",
                "esp" => "ESP",
                "offer_id" => "Offer ID",
                "is_email_duplicate" => "Is Email Duplicate",
                "eoapi_success" => "EOAPI Success",
                "is_ongage" => "Is Ongage",
                "ongage_response" => "Ongage Response",
                "ongage_at" => "Ongage At"
            ];

            return view('pages.leads', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching leads");
            return redirect()->back()->with('error', 'Failed to load leads listing.');
        }
    }

    public function show($id)
    {
        try {
            $lead = AllContact::findOrFail($id);

            $leadDetails = [
                [
                    'Basic Lead Information' => [
                        'Name' => $lead->first_name . ' ' . $lead->last_name,
                        'Email' => $lead->email,
                        'Phone' => $this->formatPhoneNumber($lead->phone),
                        'Email Domain' => $lead->email_domain,
                        'Optin Domain' => $lead->optin_domain,
                        'Domain ABT' => $lead->domain_abt
                    ],
                    'Identifiers' => [
                        'Cake Lead ID' => $lead->cake_leadid,
                        'Journya' => $lead->journya,
                        'Trusted Form' => $lead->trusted_form
                    ],
                ],
                [
                    'Tracking & IDs' => [
                        'Affiliate ID' => $lead->aff_id,
                        'Sub ID' => $lead->sub_id,
                        'IP Address' => $lead->ip_address,
                        'ESP' => $lead->esp,
                        'Offer ID' => $lead->offer_id
                    ],
                    'Email Validation' => [
                        'Result' => $lead->result,
                        'Result ID' => $lead->resultid,
                        'Response' => $lead->response
                    ],
                ],
                [
                    'Ongage Information' => [
                        'Is Ongage' => $lead->is_ongage ? 'Yes' : 'No',
                        'Ongage Response' => $lead->ongage_response,
                        'Ongage At' => $lead->ongage_at
                    ],
                    'Status Flags' => [
                        'Is Email Duplicate' => $lead->is_email_duplicate ? 'Yes' : 'No',
                        'EOAPI Success' => $lead->eoapi_success ? 'Yes' : 'No'
                    ],
                ],
                [
                    'Metadata' => [
                        'Created At' => $lead->created_at,
                        'Updated At' => $lead->updated_at
                    ]
                ]
            ];

            return view('pages.lead_show', compact('leadDetails'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving lead details");
            return redirect()->back()->with('error', 'Unable to retrieve lead details.');
        }
    }

    public function saveLeadFieldSetting(Request $request) {
        try {
            $fields = $request->input('fields');
            $jsonData = json_encode($fields);
            $userId = auth()->id();

            if($jsonData) {
                Setting::updateOrCreate(
                    ['user_id' => $userId],
                    ['lead_fields' => $jsonData]
                );
            } else {
                resetLeadFieldSetting();
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveLeadFieldSetting method while saving lead field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetLeadFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['lead_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetLeadFieldSetting method while resetting lead field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    public function leadsReport(Request $request) {
        if ($request->ajax()) {
            $filter = $request->post('filter', 'daily'); // Default to daily
            $dateValue = $request->post('date_value'); // Get the selected date, month, or year

            $totalLeadsCount = AllContact::count();

            // Apply filtering based on selected period (using created_at since import_date is removed)
            $query = AllContact::query();
            if ($filter === 'daily' && $dateValue) {
                $query->whereDate('created_at', $dateValue);
            } elseif ($filter === 'monthly' && $dateValue) {
                $query->whereYear('created_at', substr($dateValue, 0, 4))
                    ->whereMonth('created_at', substr($dateValue, 5, 2));
            } elseif ($filter === 'yearly' && $dateValue) {
                $query->whereYear('created_at', $dateValue);
            }

            // Get filtered data - simplified without list_id grouping
            $data = [['lead_count' => $query->count()]];

            // ✅ Calculate total lead count separately
            $filteredTotalLeadsCount = AllContact::where(function ($q) use ($filter, $dateValue) {
                if ($filter === 'daily' && $dateValue) {
                    $q->whereDate('created_at', $dateValue);
                } elseif ($filter === 'monthly' && $dateValue) {
                    $q->whereYear('created_at', substr($dateValue, 0, 4))
                        ->whereMonth('created_at', substr($dateValue, 5, 2));
                } elseif ($filter === 'yearly' && $dateValue) {
                    $q->whereYear('created_at', $dateValue);
                }
            })->count();

            // ✅ Return total count along with data
            return response()->json([
                'total_leads_count' => $totalLeadsCount,
                'filtered_total_leads_count' => $filteredTotalLeadsCount,
                'filter' => $filter,
                'date_value' => $dateValue,
                'data' => $data
            ]);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    public function getTestLeads(Request $request)
    {
        try {
            // Find leads where first_name or last_name contains ckmtest/ckmtestpixel
            // AND email contains ckmtest or ckmtestpixel
            $testLeads = AllContact::where(function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'LIKE', '%ckmtest%')
                      ->orWhere('last_name', 'LIKE', '%ckmtest%')
                      ->orWhere('first_name', 'LIKE', '%ckmtestpixel%')
                      ->orWhere('last_name', 'LIKE', '%ckmtestpixel%');
                })
                ->where(function ($q) {
                    $q->where('email', 'LIKE', '%ckmtest%')
                      ->orWhere('email', 'LIKE', '%ckmtestpixel%');
                });
            })
            ->select('id', 'first_name', 'last_name', 'email', 'cake_leadid', 'phone', 'created_at')
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
                    'name' => $lead->first_name . ' ' . $lead->last_name,
                    'email' => $lead->email,
                    'cake_leadid' => $lead->cake_leadid,
                    'phone' => $this->formatPhoneNumber($lead->phone),
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
            // Find and delete leads where first_name or last_name contains ckmtest/ckmtestpixel
            // AND email contains ckmtest or ckmtestpixel
            $deletedCount = AllContact::where(function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'LIKE', '%ckmtest%')
                      ->orWhere('last_name', 'LIKE', '%ckmtest%')
                      ->orWhere('first_name', 'LIKE', '%ckmtestpixel%')
                      ->orWhere('last_name', 'LIKE', '%ckmtestpixel%');
                })
                ->where(function ($q) {
                    $q->where('email', 'LIKE', '%ckmtest%')
                      ->orWhere('email', 'LIKE', '%ckmtestpixel%');
                });
            })->delete();

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

    /**
     * Format phone number to (XXX) XXX-XXXX format
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Format as (XXX) XXX-XXXX if 10 digits
        if (strlen($cleaned) === 10) {
            return '(' . substr($cleaned, 0, 3) . ') ' . substr($cleaned, 3, 3) . '-' . substr($cleaned, 6);
        }

        // Return original if not 10 digits
        return $phone;
    }
}
