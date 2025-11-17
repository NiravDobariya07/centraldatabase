<?php

namespace App\Http\Controllers;

use App\Models\{ Lead, CampaignListId, SourceSite };
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
                $leads = Lead::select(['*']);

                if (!empty($request->search_value)) {
                    $leads->whereRaw("to_tsvector('english', search_vector) @@ websearch_to_tsquery(?)", [$request->search_value]);
                }

                if (!empty($request->source_site_id) && is_array($request->source_site_id)) {
                    $leads->whereIn('source_site_id', $request->source_site_id);
                }

                if (!empty($request->campaign_list_id) && is_array($request->campaign_list_id)) {
                    $leads->whereIn('campaign_list_id', $request->campaign_list_id);
                }

                if (!empty($request->date_subscribed_from) && !empty($request->date_subscribed_to)) {
                    $dateSubscribedFrom = Carbon::parse($request->date_subscribed_from)->startOfDay();
                    $dateSubscribedTo = Carbon::parse($request->date_subscribed_to)->endOfDay();

                    $leads->whereBetween('date_subscribed', [$dateSubscribedFrom, $dateSubscribedTo]);
                }

                if (!empty($request->import_date_from) && !empty($request->import_date_to)) {
                    $importDateFrom = Carbon::parse($request->import_date_from)->startOfDay();
                    $importDateTo = Carbon::parse($request->import_date_to)->endOfDay();

                    $leads->whereBetween('import_date', [$importDateFrom, $importDateTo]);
                }

                if (!empty($request->dob_from) && !empty($request->dob_to)) {
                    $leads->whereBetween('dob', [$request->dob_from, $request->dob_to]);
                }

                // Amount-based filtering (greater than or less than)
                if (
                    isset($request->tax_debt_amount_operator, $request->tax_debt_amount) &&
                    $request->tax_debt_amount !== null &&
                    $request->tax_debt_amount !== ''
                ) {
                    $leads->where('tax_debt_amount', $request->tax_debt_amount_operator, $request->tax_debt_amount);
                }

                if (
                    isset($request->cc_debt_amount_operator, $request->cc_debt_amount) &&
                    $request->cc_debt_amount !== null &&
                    $request->cc_debt_amount !== ''
                ) {
                    $leads->where('cc_debt_amount', $request->cc_debt_amount_operator, $request->cc_debt_amount);
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

            $campaignListIds = CampaignListId::select('id', 'list_id')->orderBy('created_at', 'desc')->get();
            $sourceSites = SourceSite::select('id', 'domain')->orderBy('domain', 'asc')->get();
            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $leadListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $leadListingSetting ? json_decode($leadListingSetting->lead_fields, true) : [];
            $defaultFields = [
                "full_name" => "Name",
                "email" => "Email",
                "phone" => "Phone",
                "alt_phone" => "Alternate Phone",
                "address" => "Address",
                "city" => "City",
                "state" => "State",
                "postal" => "Postal Code",
                "country" => "Country",
                "ip" => "IP Address",
                "date_subscribed" => "Date Subscribed",
                "gender" => "Gender",
                "offer_url" => "Offer URL",
                "dob" => "Date of Birth",
                "tax_debt_amount" => "Tax Debt Amount",
                "cc_debt_amount" => "Credit Card Debt Amount",
                "type_of_debt" => "Type of Debt",
                "home_owner" => "Home Owner",
                "list_id" => "List ID",
                "import_date" => "Import Date",
                "jornaya_id" => "Jornaya ID",
                "phone_type" => "Phone Type",
                "trusted_form_id" => "Trusted Form ID",
                "opt_in" => "Opt-in",
                "sub_id_1" => "Sub ID 1",
                "sub_id_2" => "Sub ID 2",
                "sub_id_3" => "Sub ID 3",
                "sub_id_4" => "Sub ID 4",
                "sub_id_5" => "Sub ID 5",
                "aff_id_1" => "Affiliate ID 1",
                "aff_id_2" => "Affiliate ID 2",
                "lead_id" => "Lead ID",
                "ef_id" => "EF ID",
                "ck_id" => "CK ID",
                "page_url" => "Page URL"
            ];

            return view('pages.leads', compact(
                'sourceSites',
                'campaignListIds',
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
            $lead = Lead::findOrFail($id);

            $leadDetails = [
                [
                    'Identifiers' => [
                        'Lead ID' => $lead->lead_id,
                        'EF ID' => $lead->ef_id,
                        'CK ID' => $lead->ck_id,
                        'Jornaya ID' => $lead->jornaya_id,
                        'Trusted Form ID' => $lead->trusted_form_id
                    ],
                    'Basic Lead Information' => [
                        // 'ID' => $lead->id,
                        'Name' => $lead->first_name . ' ' . $lead->last_name,
                        'Email' => $lead->email,
                        'Phone' => $lead->phone,
                        'Alt Phone' => $lead->alt_phone,
                        'Gender' => $lead->gender,
                        'Date of Birth' => $lead->dob
                    ],
                ],
                [
                    'Address & Location' => [
                        'Address' => $lead->address,
                        'City' => $lead->city,
                        'State' => $lead->state,
                        'Postal Code' => $lead->postal,
                        'Country' => $lead->country
                    ],
                    'Subscription & Offer Details' => [
                        'Date Subscribed' => $lead->date_subscribed,
                        'Offer URL' => $lead->offer_url,
                        'Opt-In URL' => $lead->opt_in
                    ],
                ],
                [
                    'Financial & Debt Information' => [
                        'Tax Debt Amount' => $lead->tax_debt_amount,
                        'Credit Card Debt' => $lead->cc_debt_amount,
                        'Type of Debt' => $lead->type_of_debt,
                        'Home Owner' => $lead->home_owner
                    ],
                    'Lead Source & Tracking' => [
                        'List ID' => !empty($lead->campaign_list_data->list_id) ? $lead->campaign_list_data->list_id : '',
                        'Import Date' => $lead->import_date,
                        'Source Site' => !empty($lead->source_site_data->domain) ? $lead->source_site_data->domain : '',
                        'Page URL' => $lead->page_url
                    ],
                ],
                [
                    'Sub IDs & Affiliate IDs' => [
                        'Sub ID 1' => $lead->sub_id_1,
                        'Sub ID 2' => $lead->sub_id_2,
                        'Sub ID 3' => $lead->sub_id_3,
                        'Sub ID 4' => $lead->sub_id_4,
                        'Sub ID 5' => $lead->sub_id_5,
                        'Aff ID 1' => $lead->aff_id_1,
                        'Aff ID 2' => $lead->aff_id_2
                    ],
                    'Metadata' => [
                        'Created At' => $lead->created_at,
                        'Updated At' => $lead->updated_at
                    ]
                ]
            ];

            if (!empty($lead->extra_fields)) {
                $leadDetails[] = ['Additional Fields' => $lead->extra_fields];
            }

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

            $totalLeadsCount = Lead::count();
            $query = Lead::with('campaignList')
                ->select('campaign_list_id')
                ->selectRaw('COUNT(*) as lead_count')
                ->groupBy('campaign_list_id');

            // Apply filtering based on selected period
            if ($filter === 'daily' && $dateValue) {
                $query->whereDate('import_date', $dateValue);
            } elseif ($filter === 'monthly' && $dateValue) {
                $query->whereYear('import_date', substr($dateValue, 0, 4))
                    ->whereMonth('import_date', substr($dateValue, 5, 2));
            } elseif ($filter === 'yearly' && $dateValue) {
                $query->whereYear('import_date', $dateValue);
            }

            // Get filtered data
            $data = $query->get()->map(function ($row) {
                return [
                    'list_id' => optional($row->campaignList)->list_id ?? 'Unassigned',
                    'lead_count' => $row->lead_count
                ];
            })->sortByDesc('lead_count')->values();

            // ✅ Calculate total lead count separately
            $filteredTotalLeadsCount = Lead::where(function ($q) use ($filter, $dateValue) {
                if ($filter === 'daily' && $dateValue) {
                    $q->whereDate('import_date', $dateValue);
                } elseif ($filter === 'monthly' && $dateValue) {
                    $q->whereYear('import_date', substr($dateValue, 0, 4))
                        ->whereMonth('import_date', substr($dateValue, 5, 2));
                } elseif ($filter === 'yearly' && $dateValue) {
                    $q->whereYear('import_date', $dateValue);
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
}
