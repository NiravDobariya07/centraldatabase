<?php

namespace App\Http\Controllers;

use App\Models\ExtLeadContact;
use App\Models\ExportFile;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class ExtLeadController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $leads = ExtLeadContact::select(['*'])->orderBy('created_date', 'desc');

                // Column-specific filter
                if (!empty($request->filter_column) && !empty($request->search_value)) {
                    $column = $request->filter_column;
                    $searchTerm = '%' . $request->search_value . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'phone' => 'phone',
                        'alt_phone' => 'alt_phone',
                        'address' => 'address',
                        'city' => 'city',
                        'state' => 'state',
                        'postal' => 'postal',
                        'country' => 'country',
                        'ip' => 'ip',
                        'source' => 'source',
                        'affid' => 'affid',
                        'subid' => 'subid',
                        'lead_id' => 'lead_id',
                        'jornaya_id' => 'jornaya_id',
                        'trusted_form_id' => 'trusted_form_id',
                        'result' => 'result',
                        'offer_url' => 'offer_url',
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
                            ->orWhere('email', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('source', 'LIKE', $searchTerm)
                            ->orWhere('affid', 'LIKE', $searchTerm)
                            ->orWhere('subid', 'LIKE', $searchTerm)
                            ->orWhere('jornaya_id', 'LIKE', $searchTerm)
                            ->orWhere('trusted_form_id', 'LIKE', $searchTerm);
                    });
                }

                // Date range filter - supports partial dates (using created_date)
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

                    $leads->whereBetween('created_date', [$startDate, $endDate]);
                } elseif (!empty($request->start_date)) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $leads->whereBetween('created_date', [$startDate, $endDate]);
                } elseif (!empty($request->end_date)) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    $leads->where('created_date', '<=', $endDate);
                }

                return DataTables::of($leads)
                ->addColumn('full_name', function ($lead) {
                    return $lead->first_name . ' ' . $lead->last_name;
                })
                ->orderColumn('full_name', function ($query, $order) {
                    $query->orderBy('first_name', $order)->orderBy('last_name', $order);
                })
                ->addColumn('action', function ($lead) {
                    return '<a href="'.route('ext-lead-listing.show', $lead->id).'" class="btn btn-sm btn-primary">View</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $extLeadListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $extLeadListingSetting && isset($extLeadListingSetting->ext_lead_fields)
                ? json_decode($extLeadListingSetting->ext_lead_fields, true)
                : [];
            $defaultFields = [
                "full_name" => "Name",
                "email" => "Email",
                "phone" => "Phone",
                "alt_phone" => "Alt Phone",
                "address" => "Address",
                "city" => "City",
                "state" => "State",
                "postal" => "Postal",
                "country" => "Country",
                "ip" => "IP",
                "date_subscribed" => "Date Subscribed",
                "gender" => "Gender",
                "offer_url" => "Offer URL",
                "dob" => "Date of Birth",
                "list_id" => "List ID",
                "import_date" => "Import Date",
                "phone_type" => "Phone Type",
                "tax_debt_amount" => "Tax Debt Amount",
                "type_of_debt" => "Type of Debt",
                "homeowner" => "Homeowner",
                "jornaya_id" => "Jornaya ID",
                "trusted_form_id" => "Trusted Form ID",
                "opt_in" => "Opt In",
                "subid1" => "Sub ID 1",
                "subid2" => "Sub ID 2",
                "subid3" => "Sub ID 3",
                "subid4" => "Sub ID 4",
                "subid5" => "Sub ID 5",
                "aff_id_1" => "Aff ID 1",
                "aff_id_2" => "Aff ID 2",
                "lead_id" => "Lead ID",
                "page_url" => "Page URL",
                "ef_id" => "EF ID",
                "ck_id" => "CK ID",
                "source" => "Source",
                "affid" => "Affid",
                "subid" => "Subid",
                "result" => "Result",
                "resultid" => "Result ID",
                "response" => "Response",
                "is_email_duplicate" => "Is Email Duplicate",
                "eoapi_success" => "EOAPI Success",
                "is_ongage" => "Is Ongage",
                "ongage_response" => "Ongage Response",
                "ongage_at" => "Ongage At",
                "created_date" => "Created Date"
            ];

            return view('pages.ext_lead_listing', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching ext leads");
            return redirect()->back()->with('error', 'Failed to load ext leads listing.');
        }
    }

    public function show($id)
    {
        try {
            $lead = ExtLeadContact::findOrFail($id);

            $leadDetails = [
                [
                    'Basic Lead Information' => [
                        'Name' => $lead->first_name . ' ' . $lead->last_name,
                        'Email' => $lead->email,
                        'Phone' => $this->formatPhoneNumber($lead->phone),
                        'Alt Phone' => $this->formatPhoneNumber($lead->alt_phone),
                        'Address' => $lead->address,
                        'City' => $lead->city,
                        'State' => $lead->state,
                        'Postal' => $lead->postal,
                        'Country' => $lead->country,
                        'IP' => $lead->ip
                    ],
                    'Personal Information' => [
                        'Date Subscribed' => $lead->date_subscribed,
                        'Gender' => $lead->gender,
                        'Date of Birth' => $lead->dob,
                        'Homeowner' => $lead->homeowner,
                        'Phone Type' => $lead->phone_type,
                        'Opt In' => $lead->opt_in
                    ],
                ],
                [
                    'Debt Information' => [
                        'Tax Debt Amount' => $lead->tax_debt_amount,
                        'Type of Debt' => $lead->type_of_debt
                    ],
                    'Identifiers' => [
                        'Lead ID' => $lead->lead_id,
                        'Jornaya ID' => $lead->jornaya_id,
                        'Trusted Form ID' => $lead->trusted_form_id,
                        'List ID' => $lead->list_id
                    ],
                ],
                [
                    'Tracking & IDs' => [
                        'Source' => $lead->source,
                        'Affid' => $lead->affid,
                        'Subid' => $lead->subid,
                        'Aff ID 1' => $lead->aff_id_1,
                        'Aff ID 2' => $lead->aff_id_2,
                        'Sub ID 1' => $lead->subid1,
                        'Sub ID 2' => $lead->subid2,
                        'Sub ID 3' => $lead->subid3,
                        'Sub ID 4' => $lead->subid4,
                        'Sub ID 5' => $lead->subid5,
                        'EF ID' => $lead->ef_id,
                        'CK ID' => $lead->ck_id
                    ],
                    'URLs' => [
                        'Offer URL' => $lead->offer_url,
                        'Page URL' => $lead->page_url
                    ],
                ],
                [
                    'Email Validation' => [
                        'Result' => $lead->result,
                        'Result ID' => $lead->resultid,
                        'Response' => $lead->response
                    ],
                    'Ongage Information' => [
                        'Is Ongage' => $lead->is_ongage ? 'Yes' : 'No',
                        'Ongage Response' => $lead->ongage_response,
                        'Ongage At' => $lead->ongage_at
                    ],
                ],
                [
                    'Status Flags' => [
                        'Is Email Duplicate' => $lead->is_email_duplicate ? 'Yes' : 'No',
                        'EOAPI Success' => $lead->eoapi_success ? 'Yes' : 'No'
                    ],
                    'Metadata' => [
                        'Import Date' => $lead->import_date,
                        'Created Date' => $lead->created_date,
                        'Updated Date' => $lead->updated_date
                    ]
                ]
            ];

            return view('pages.ext_lead_listing_show', compact('leadDetails'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving ext lead details");
            return redirect()->back()->with('error', 'Unable to retrieve ext lead details.');
        }
    }

    public function saveExtLeadFieldSetting(Request $request) {
        try {
            $fields = $request->input('fields');
            $jsonData = json_encode($fields);
            $userId = auth()->id();

            if($jsonData) {
                Setting::updateOrCreate(
                    ['user_id' => $userId],
                    ['ext_lead_fields' => $jsonData]
                );
            } else {
                Setting::where('user_id', $userId)->update(['ext_lead_fields' => json_encode([])]);
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveExtLeadFieldSetting method while saving ext lead field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetExtLeadFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['ext_lead_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetExtLeadFieldSetting method while resetting ext lead field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    public function getTestLeads(Request $request)
    {
        try {
            // Find leads where first_name or last_name contains ckmtest/ckmtestpixel
            // AND email contains ckmtest or ckmtestpixel
            $testLeads = ExtLeadContact::where(function ($query) {
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
            ->select('id', 'first_name', 'last_name', 'email', 'lead_id', 'phone', 'created_date')
            ->orderBy('created_date', 'desc')
            ->get();

            // Get date range
            $dateRange = null;
            if ($testLeads->count() > 0) {
                $minDate = $testLeads->min('created_date');
                $maxDate = $testLeads->max('created_date');
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
                    'lead_id' => $lead->lead_id,
                    'phone' => $this->formatPhoneNumber($lead->phone),
                    'created_date' => $lead->created_date ? $lead->created_date->format('Y-m-d H:i:s') : 'N/A'
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
            $deletedCount = ExtLeadContact::where(function ($query) {
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

