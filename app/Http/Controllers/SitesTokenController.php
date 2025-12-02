<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Constants\AppConstants;
use Carbon\Carbon;


class SitesTokenController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $tokens = Offer::select(['*'])->orderBy('created_at', 'desc');

                // Column-specific filter
                if (!empty($request->filter_column) && !empty($request->search_value)) {
                    $column = $request->filter_column;
                    $searchTerm = '%' . $request->search_value . '%';

                    // Map frontend column names to database column names
                    $columnMapping = [
                        'offer_name' => 'offer_name',
                        'domain_abt' => 'domain_abt',
                    ];

                    // Get the actual database column name
                    $dbColumn = $columnMapping[$column] ?? $column;

                    // Apply filter on the specific column
                    if (in_array($dbColumn, array_values($columnMapping))) {
                        $tokens->where($dbColumn, 'LIKE', $searchTerm);
                    }
                } elseif (!empty($request->search_value)) {
                    // Fallback: if no column is selected, search across all common fields
                    $searchTerm = '%' . $request->search_value . '%';
                    $tokens->where(function ($query) use ($searchTerm) {
                        $query->where('offer_name', 'LIKE', $searchTerm)
                            ->orWhere('domain_abt', 'LIKE', $searchTerm);
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

                    $tokens->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->start_date)) {
                    // Only start date provided - filter from start date to today
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                    $tokens->whereBetween('created_at', [$startDate, $endDate]);
                } elseif (!empty($request->end_date)) {
                    // Only end date provided - filter from beginning to end date
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                    $tokens->where('created_at', '<=', $endDate);
                }

                return DataTables::of($tokens)
                ->addColumn('masked_token', function ($token) {
                    // Mask the token: show first 3 chars, then ***, then last part (base64-like format)
                    $tokenValue = $token->auth_token ?? '';
                    if (strlen($tokenValue) > 6) {
                        // Format like: aHR***Q== (first 3 chars, then ***, then last part)
                        $firstPart = substr($tokenValue, 0, 3);
                        $lastPart = substr($tokenValue, -4); // Last 4 chars to match the image format
                        return $firstPart . '***' . $lastPart;
                    }
                    return $tokenValue ? '***' : 'N/A';
                })
                ->addColumn('action', function ($token) {
                    return '<button type="button" class="btn btn-sm btn-warning regenerate-token-btn" data-token-id="'.$token->id.'" data-token-name="'.htmlspecialchars($token->offer_name ?? 'N/A').'" data-domain-name="'.htmlspecialchars($token->domain_abt ?? 'N/A').'">Regenerate Token</button>';
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action'])
                ->make(true);
            }

            $exportDaysOfWeek = AppConstants::EXPORT_DAYS_OF_WEEK;
            $userId = auth()->id();
            $tokenListingSetting = Setting::where('user_id', $userId)->first();
            $selectedFields = $tokenListingSetting && isset($tokenListingSetting->site_token_fields)
                ? json_decode($tokenListingSetting->site_token_fields, true)
                : [];
            $defaultFields = [
                "offer_name" => "Domain Name",
                "domain_abt" => "Domain Abbr",
                "auth_token" => "Token",
            ];

            return view('pages.sites_tokens', compact(
                'exportDaysOfWeek',
                'selectedFields',
                'defaultFields'
            ));
        } catch (\Exception $e) {
            reportException($e, "Error in index method while fetching sites tokens");
            return redirect()->back()->with('error', 'Failed to load sites tokens listing.');
        }
    }

    public function show($id)
    {
        try {
            $token = Offer::findOrFail($id);

            $tokenDetails = [
                [
                    'Token Information' => [
                        'Domain Name' => $token->offer_name,
                        'Domain Abbr' => $token->domain_abt,
                        'Auth Token' => $token->auth_token,
                    ],
                    'Metadata' => [
                        'Created At' => $token->created_at,
                        'Updated At' => $token->updated_at
                    ]
                ]
            ];

            return view('pages.sites_token_show', compact('tokenDetails'));
        } catch (\Exception $e) {
            reportException($e, "Error in show method while retrieving sites token details");
            return redirect()->back()->with('error', 'Unable to retrieve sites token details.');
        }
    }

    public function saveTokenFieldSetting(Request $request) {
        try {
            $fields = $request->input('fields');
            $jsonData = json_encode($fields);
            $userId = auth()->id();

            if($jsonData) {
                $setting = Setting::where('user_id', $userId)->first();
                if ($setting) {
                    $setting->site_token_fields = $jsonData;
                    $setting->save();
                } else {
                    Setting::create([
                        'user_id' => $userId,
                        'site_token_fields' => $jsonData
                    ]);
                }
            } else {
                Setting::where('user_id', $userId)->update(['site_token_fields' => json_encode([])]);
            }

            return response()->json(['message' => 'Fields saved successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in saveTokenFieldSetting method while saving sites token field settings");
            return response()->json(['message' => 'Failed to save settings.'], 500);
        }
    }

    public function resetTokenFieldSetting() {
        try {
            $userId = auth()->id();
            Setting::where('user_id', $userId)->update(['site_token_fields' => json_encode([])]);

            return response()->json(['message' => 'Fields setting reset successfully!']);
        } catch (\Exception $e) {
            reportException($e, "Error in resetTokenFieldSetting method while resetting sites token field settings");
            return response()->json(['message' => 'Failed to reset settings.'], 500);
        }
    }

    public function getTestTokens(Request $request)
    {
        try {
            // Find tokens where offer_name contains test
            $testTokens = Offer::where('offer_name', 'LIKE', '%test%')
                ->select('id', 'offer_name', 'domain_abt', 'auth_token', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            // Get date range
            $dateRange = null;
            if ($testTokens->count() > 0) {
                $minDate = $testTokens->min('created_at');
                $maxDate = $testTokens->max('created_at');
                if ($minDate && $maxDate) {
                    $dateRange = Carbon::parse($minDate)->format('Y-m-d H:i:s') . ' To ' . Carbon::parse($maxDate)->format('Y-m-d H:i:s');
                }
            }

            // Format the data
            $formattedTokens = $testTokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'offer_name' => $token->offer_name ?? 'N/A',
                    'domain_abt' => $token->domain_abt ?? 'N/A',
                    'auth_token' => $token->auth_token ?? 'N/A',
                    'created_at' => $token->created_at ? $token->created_at->format('Y-m-d H:i:s') : 'N/A'
                ];
            });

            return response()->json([
                'success' => true,
                'total_count' => $testTokens->count(),
                'display_count' => $testTokens->count(),
                'date_range' => $dateRange,
                'tokens' => $formattedTokens
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in getTestTokens method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test tokens.'
            ], 500);
        }
    }

    public function deleteTestTokens(Request $request)
    {
        try {
            // Find and delete tokens where offer_name contains test
            $deletedCount = Offer::where('offer_name', 'LIKE', '%test%')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} test token(s).",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in deleteTestTokens method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test tokens.'
            ], 500);
        }
    }

    public function regenerateToken(Request $request, $id)
    {
        try {
            $token = Offer::findOrFail($id);

            // Generate a new random token (base64 encoded string)
            $newToken = base64_encode(random_bytes(32));
            // Remove padding if any and limit to 100 chars as per database
            $newToken = substr(rtrim($newToken, '='), 0, 100);

            $token->auth_token = $newToken;
            $token->save();

            return response()->json([
                'success' => true,
                'message' => 'Token regenerated successfully!',
                'token' => $newToken
            ]);
        } catch (\Exception $e) {
            reportException($e, "Error in regenerateToken method");
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate token.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'offer_name' => [
                    'required',
                    'string',
                    'max:255',
                    'url',
                    'unique:offers,offer_name,NULL,id,deleted_at,NULL'
                ],
                'domain_abt' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:offers,domain_abt,NULL,id,deleted_at,NULL'
                ]
            ], [
                'offer_name.required' => 'Domain Name is required.',
                'offer_name.url' => 'Please enter a valid URL (e.g., https://www.example.com).',
                'offer_name.max' => 'Domain Name must not exceed 255 characters.',
                'offer_name.unique' => 'This Domain Name already exists. Please use a different domain.',
                'domain_abt.required' => 'Domain Abbr is required.',
                'domain_abt.max' => 'Domain Abbr must not exceed 255 characters.',
                'domain_abt.unique' => 'This Domain Abbr already exists. Please use a different abbreviation.'
            ]);

            // Generate token
            $authToken = base64_encode(random_bytes(32));
            $authToken = substr(rtrim($authToken, '='), 0, 100);

            // Create the offer
            $offer = Offer::create([
                'domain_abt' => $validated['domain_abt'],
                'offer_name' => $validated['offer_name'],
                'auth_token' => $authToken
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token created successfully!',
                'data' => [
                    'id' => $offer->id,
                    'domain_abt' => $offer->domain_abt,
                    'offer_name' => $offer->offer_name
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            reportException($e, "Error in store method while creating token");
            return response()->json([
                'success' => false,
                'message' => 'Failed to create token. Please try again.'
            ], 500);
        }
    }
}

