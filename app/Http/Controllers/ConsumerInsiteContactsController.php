<?php

namespace App\Http\Controllers;

use App\Models\ConsumerInsiteContact;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class ConsumerInsiteContactsController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $contacts = ConsumerInsiteContact::with('categories')->select(['*'])->where('deleted_at', 0)->orderBy('created_at', 'desc');

                // Column-specific filter
                if (!empty($request->filter_column) && !empty($request->search_value)) {
                    $column = $request->filter_column;
                    $searchTerm = '%' . $request->search_value . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'first_name' => 'first_name',
                        'last_name' => 'last_name',
                        'email' => 'email',
                        'age' => 'age',
                        'credit_score' => 'credit_score',
                        'location_name' => 'location_name',
                        'result' => 'result',
                        'resultid' => 'resultid',
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
                            ->orWhere('age', 'LIKE', $searchTerm)
                            ->orWhere('credit_score', 'LIKE', $searchTerm)
                            ->orWhere('location_name', 'LIKE', $searchTerm)
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
                ->addColumn('categories', function ($contact) {
                    // Return categories data as JSON string for the modal
                    $categories = $contact->categories;
                    if ($categories && $categories->count() > 0) {
                        $categoryNames = $categories->pluck('category_name')->toArray();
                        // Return as JSON string - DataTables will handle it
                        return json_encode($categoryNames);
                    }
                    return json_encode([]);
                })
                ->addColumn('action', function ($contact) {
                    return '<a href="'.route('consumer-insite-contacts.show', $contact->id).'" class="btn btn-sm btn-primary">View</a>';
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'categories'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $contactListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $contactListingSetting && isset($contactListingSetting->consumer_insite_contact_fields)
                ? json_decode($contactListingSetting->consumer_insite_contact_fields, true)
                : [];
            $defaultFields = [
                "full_name" => "Name",
                "email" => "Email",
                "age" => "Age",
                "credit_score" => "Credit Score",
                "location_name" => "Location Name",
                "categories" => "Categories",
                "result" => "Result",
                "resultid" => "Result ID",
                "created_at" => "Created At",
                "updated_at" => "Updated At",
                "deleted_at" => "Deleted At"
            ];

            return view('pages.consumer_insite_contacts', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching consumer insite contacts");
            return redirect()->back()->with('error', 'Failed to load consumer insite contacts listing.');
        }
    }

    public function show($id)
    {
        try {
            $contact = ConsumerInsiteContact::with('categories')->where('deleted_at', 0)->findOrFail($id);

            $contactDetails = [
                [
                    'Basic Contact Information' => [
                        'Name' => $contact->first_name . ' ' . $contact->last_name,
                        'Email' => $contact->email,
                        'Age' => $contact->age,
                        'Credit Score' => $contact->credit_score,
                        'Location Name' => $contact->location_name,
                        'Categories' => $contact->categories->count() > 0 ? $contact->categories->pluck('category_name')->implode(', ') : 'N/A'
                    ],
                    'Email Validation' => [
                        'Result' => $contact->result,
                        'Result ID' => $contact->resultid,
                        'Response' => $contact->response
                    ],
                ],
                [
                    'Ongage Information' => [
                        'Is Ongage' => $contact->is_ongage ? 'Yes' : 'No',
                        'Ongage Response' => $contact->ongage_response
                    ],
                    'Status Flags' => [
                        'Is Email Duplicate' => $contact->is_email_duplicate ? 'Yes' : 'No',
                        'EOAPI Success' => $contact->eoapi_success ? 'Yes' : 'No',
                        'Deleted At' => $contact->deleted_at ? 'Yes' : 'No'
                    ],
                ],
                [
                    'Metadata' => [
                        'Created At' => $contact->created_at,
                        'Updated At' => $contact->updated_at
                    ]
                ]
            ];

            return view('pages.consumer_insite_contact_show', compact('contactDetails'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving consumer insite contact details");
            return redirect()->back()->with('error', 'Unable to retrieve consumer insite contact details.');
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
                    $setting->consumer_insite_contact_fields = $jsonData;
                    $setting->save();
                } else {
                    Setting::create([
                        'user_id' => $userId,
                        'consumer_insite_contact_fields' => $jsonData
                    ]);
                }
            } else {
                Setting::where('user_id', $userId)->update(['consumer_insite_contact_fields' => json_encode([])]);
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveContactFieldSetting method while saving contact field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetContactFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['consumer_insite_contact_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetContactFieldSetting method while resetting contact field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    public function getTestContacts(Request $request)
    {
        try {
            // Find contacts where first_name or last_name contains ckmtest/ckmtestpixel
            // AND email contains ckmtest or ckmtestpixel
            $testContacts = ConsumerInsiteContact::where('deleted_at', 0)
                ->where(function ($query) {
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
                ->select('id', 'first_name', 'last_name', 'email', 'age', 'credit_score', 'created_at')
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
                    'age' => $contact->age ?? 'N/A',
                    'credit_score' => $contact->credit_score ?? 'N/A',
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
            $deletedCount = ConsumerInsiteContact::where('deleted_at', 0)
                ->where(function ($query) {
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
