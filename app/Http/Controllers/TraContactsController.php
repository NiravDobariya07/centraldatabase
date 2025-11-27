<?php

namespace App\Http\Controllers;

use App\Models\TraContact;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class TraContactsController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $contacts = TraContact::select(['*'])->orderBy('created_at', 'desc');

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
                        'state' => 'state',
                        'zip_code' => 'zip_code',
                        'cake_id' => 'cake_id',
                        'aff_id' => 'aff_id',
                        'sub_id' => 'sub_id',
                        'offer_id' => 'offer_id',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $contacts->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($request->search_value)) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $request->search_value . '%';
                    $contacts->where(function ($query) use ($searchTerm) {
                        $query->where('email', 'LIKE', $searchTerm)
                            ->orWhere('first_name', 'LIKE', $searchTerm)
                            ->orWhere('last_name', 'LIKE', $searchTerm)
                            ->orWhere('phone', 'LIKE', $searchTerm)
                            ->orWhere('cake_id', 'LIKE', $searchTerm)
                            ->orWhere('email_domain', 'LIKE', $searchTerm)
                            ->orWhere('state', 'LIKE', $searchTerm)
                            ->orWhere('zip_code', 'LIKE', $searchTerm);
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

                    $contacts->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->start_date)) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $contacts->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->end_date)) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    $contacts->where('created_at', '<=', $endDate);
                }

                return DataTables::of($contacts)
                ->addColumn('full_name', function ($contact) {
                    return $contact->first_name . ' ' . $contact->last_name;
                })
                ->orderColumn('full_name', function ($query, $order) {
                    $query->orderBy('first_name', $order)->orderBy('last_name', $order);
                })
                ->addColumn('action', function ($contact) {
                    return '<a href="'.route('tra-contacts.show', $contact->id).'" class="btn btn-sm btn-primary">View</a>';
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $contactListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $contactListingSetting && isset($contactListingSetting->tra_contact_fields)
                ? json_decode($contactListingSetting->tra_contact_fields, true)
                : [];
            $defaultFields = [
                "full_name" => "Name",
                "email" => "Email",
                "email_domain" => "Email Domain",
                "phone" => "Phone",
                "page" => "Page",
                "tax_debt" => "Tax Debt",
                "state" => "State",
                "zip_code" => "Zip Code",
                "universal_leadid" => "Universal Lead ID",
                "aff_id" => "Affiliate ID",
                "optin_domain" => "Optin Domain",
                "cake_id" => "Cake ID",
                "ckm_campaign_id" => "CKM Campaign ID",
                "ckm_key" => "CKM Key",
                "sub_id" => "Sub ID",
                "ip_address" => "IP Address",
                "offer_id" => "Offer ID",
                "lead_time_stamp" => "Lead Time Stamp",
                "created_at" => "Created At",
                "updated_at" => "Updated At"
            ];

            return view('pages.tra_contacts', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching TRA contacts");
            return redirect()->back()->with('error', 'Failed to load TRA contacts listing.');
        }
    }

    public function show($id)
    {
        try {
            $contact = TraContact::findOrFail($id);

            $contactDetails = [
                [
                    'Basic Contact Information' => [
                        'Name' => $contact->first_name . ' ' . $contact->last_name,
                        'Email' => $contact->email,
                        'Email Domain' => $contact->email_domain,
                        'Phone' => $contact->phone,
                        'State' => $contact->state,
                        'Zip Code' => $contact->zip_code,
                        'Page' => $contact->page,
                    ],
                    'Lead Information' => [
                        'Optin Domain' => $contact->optin_domain,
                        'Universal Lead ID' => $contact->universal_leadid,
                        'Cake ID' => $contact->cake_id,
                        'Lead Time Stamp' => $contact->lead_time_stamp,
                    ],
                ],
                [
                    'Campaign Information' => [
                        'CKM Campaign ID' => $contact->ckm_campaign_id,
                        'CKM Key' => $contact->ckm_key,
                        'Tax Debt' => $contact->tax_debt,
                    ],
                    'Tracking Information' => [
                        'Affiliate ID' => $contact->aff_id,
                        'Sub ID' => $contact->sub_id,
                        'IP Address' => $contact->ip_address,
                        'Offer ID' => $contact->offer_id,
                    ],
                ],
                [
                    'Additional Information' => [
                        'Response' => $contact->response,
                    ],
                    'Metadata' => [
                        'Created At' => $contact->created_at,
                        'Updated At' => $contact->updated_at
                    ]
                ]
            ];

            return view('pages.tra_contact_show', compact('contactDetails'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving TRA contact details");
            return redirect()->back()->with('error', 'Unable to retrieve TRA contact details.');
        }
    }

    public function saveContactFieldSetting(Request $request) {
        try {
            $fields = $request->input('fields');
            $jsonData = json_encode($fields);
            $userId = auth()->id();

            if($jsonData) {
                $setting = Setting::where('user_id', $userId)->first();
                if ($setting) {
                    $setting->tra_contact_fields = $jsonData;
                    $setting->save();
                } else {
                    Setting::create([
                        'user_id' => $userId,
                        'tra_contact_fields' => $jsonData
                    ]);
                }
            } else {
                Setting::where('user_id', $userId)->update(['tra_contact_fields' => json_encode([])]);
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveContactFieldSetting method while saving TRA contact field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetContactFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['tra_contact_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetContactFieldSetting method while resetting TRA contact field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    public function getTestContacts(Request $request)
    {
        try {
            // Find contacts where first_name or last_name contains ckmtest/ckmtestpixel
            // AND email contains ckmtest or ckmtestpixel
            $testContacts = TraContact::where(function ($query) {
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
                ->select('id', 'first_name', 'last_name', 'email', 'phone', 'state', 'cake_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get date range
            $dateRange = null;
            if ($testContacts->count() > 0) {
                $minDate = $testContacts->min('created_at');
                $maxDate = $testContacts->max('created_at');
                if ($minDate && $maxDate) {
                    $dateRange = Carbon::parse($minDate)->format('Y-m-d H:i:s') . ' To ' . Carbon::parse($maxDate)->format('Y-m-d H:i:s');
                }
            }

            // Format the data
            $formattedContacts = $testContacts->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->first_name . ' ' . $contact->last_name,
                    'email' => $contact->email,
                    'phone' => $contact->phone ?? 'N/A',
                    'state' => $contact->state ?? 'N/A',
                    'cake_id' => $contact->cake_id ?? 'N/A',
                    'created_at' => $contact->created_at ? $contact->created_at->format('Y-m-d H:i:s') : 'N/A'
                ];
            });

            return response()->json([
                'success' => true,
                'total_count' => $testContacts->count(),
                'display_count' => $testContacts->count(),
                'date_range' => $dateRange,
                'contacts' => $formattedContacts
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in getTestContacts method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test contacts.'
            ], 500);
        }
    }

    public function deleteTestContacts(Request $request)
    {
        try {
            // Find and delete contacts where first_name or last_name contains ckmtest/ckmtestpixel
            // AND email contains ckmtest or ckmtestpixel
            $deletedCount = TraContact::where(function ($query) {
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
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} test contact(s).",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in deleteTestContacts method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test contacts.'
            ], 500);
        }
    }
}

