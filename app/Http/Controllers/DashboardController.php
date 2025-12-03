<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AllContact;
use App\Models\ConsumerInsiteContact;
use App\Models\TraContact;
use App\Models\FlmApiLead;
use App\Models\Offer;
use App\Models\BlacklistListing;
use App\Models\ExtLeadContact;

class DashboardController extends Controller
{
    /**
     * Get dashboard counts for all listing types with filters
     */
    public function getDashboardCounts(Request $request)
    {
        if ($request->ajax()) {
            $filter = $request->post('filter', 'daily'); // Default to daily
            $dateValue = $request->post('date_value'); // Get the selected date, month, or year

            // Helper function to apply date filters
            $applyDateFilter = function ($query, $dateColumn, $filter, $dateValue) {
                if ($filter === 'daily' && $dateValue) {
                    $query->whereDate($dateColumn, $dateValue);
                } elseif ($filter === 'yesterday' && $dateValue) {
                    // Yesterday filter uses the same logic as daily
                    $query->whereDate($dateColumn, $dateValue);
                } elseif ($filter === 'monthly' && $dateValue) {
                    $query->whereYear($dateColumn, substr($dateValue, 0, 4))
                        ->whereMonth($dateColumn, substr($dateValue, 5, 2));
                } elseif ($filter === 'yearly' && $dateValue) {
                    $query->whereYear($dateColumn, $dateValue);
                }
                // For 'total' filter, no date filtering is applied (returns all records)
            };

            // Get total counts (all time)
            $totalCounts = [
                'leads' => AllContact::count(),
                'consumer_insite' => ConsumerInsiteContact::count(),
                'tra_lead' => TraContact::count(),
                'whitecollar_lead' => FlmApiLead::count(),
                'sites_token' => Offer::count(),
                'blacklist_list' => BlacklistListing::count(),
                'ext_lead' => ExtLeadContact::count(),
            ];

            // Get filtered counts
            $filteredCounts = [];

            // If filter is 'total', use total counts directly (no date filtering)
            if ($filter === 'total') {
                $filteredCounts = $totalCounts;
            } else {
                // Leads (AllContact) - uses created_at
                $leadsQuery = AllContact::query();
                $applyDateFilter($leadsQuery, 'created_at', $filter, $dateValue);
                $filteredCounts['leads'] = $leadsQuery->count();

                // Consumer Insite - uses created_at
                $consumerInsiteQuery = ConsumerInsiteContact::query();
                $applyDateFilter($consumerInsiteQuery, 'created_at', $filter, $dateValue);
                $filteredCounts['consumer_insite'] = $consumerInsiteQuery->count();

                // TRA Lead - uses created_at
                $traLeadQuery = TraContact::query();
                $applyDateFilter($traLeadQuery, 'created_at', $filter, $dateValue);
                $filteredCounts['tra_lead'] = $traLeadQuery->count();

                // WhiteCollar Lead - uses created_at
                $whitecollarLeadQuery = FlmApiLead::query();
                $applyDateFilter($whitecollarLeadQuery, 'created_at', $filter, $dateValue);
                $filteredCounts['whitecollar_lead'] = $whitecollarLeadQuery->count();

                // Sites Token (Offer) - uses created_at
                $sitesTokenQuery = Offer::query();
                $applyDateFilter($sitesTokenQuery, 'created_at', $filter, $dateValue);
                $filteredCounts['sites_token'] = $sitesTokenQuery->count();

                // Blacklist List - uses created_at
                $blacklistQuery = BlacklistListing::query();
                $applyDateFilter($blacklistQuery, 'created_at', $filter, $dateValue);
                $filteredCounts['blacklist_list'] = $blacklistQuery->count();

                // Ext Lead - uses created_date (not created_at)
                $extLeadQuery = ExtLeadContact::query();
                $applyDateFilter($extLeadQuery, 'created_date', $filter, $dateValue);
                $filteredCounts['ext_lead'] = $extLeadQuery->count();
            }

            // Format data for table display
            $data = [
                ['listing_type' => 'Leads', 'count' => $filteredCounts['leads']],
                ['listing_type' => 'Consumer Insite', 'count' => $filteredCounts['consumer_insite']],
                ['listing_type' => 'TRA Lead', 'count' => $filteredCounts['tra_lead']],
                ['listing_type' => 'WhiteCollar Lead', 'count' => $filteredCounts['whitecollar_lead']],
                ['listing_type' => 'Sites Token', 'count' => $filteredCounts['sites_token']],
                ['listing_type' => 'Blacklist List', 'count' => $filteredCounts['blacklist_list']],
                ['listing_type' => 'Ext Lead', 'count' => $filteredCounts['ext_lead']],
            ];

            return response()->json([
                'total_counts' => $totalCounts,
                'filtered_counts' => $filteredCounts,
                'filter' => $filter,
                'date_value' => $dateValue,
                'data' => $data
            ]);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    /**
     * Get the earliest year from all lead tables
     */
    public function getEarliestYear(Request $request)
    {
        try {
            $earliestDates = [];

            // Get earliest created_at from each table
            $earliestDates[] = AllContact::min('created_at');
            $earliestDates[] = ConsumerInsiteContact::min('created_at');
            $earliestDates[] = TraContact::min('created_at');
            $earliestDates[] = FlmApiLead::min('created_at');
            $earliestDates[] = Offer::min('created_at');
            $earliestDates[] = BlacklistListing::min('created_at');
            $earliestDates[] = ExtLeadContact::min('created_date'); // Note: ExtLeadContact uses created_date

            // Filter out null values and get the earliest date
            $earliestDates = array_filter($earliestDates);

            if (empty($earliestDates)) {
                // If no data exists, default to current year
                $earliestYear = (int) date('Y');
            } else {
                // Find the earliest date
                $earliestDate = min($earliestDates);
                $earliestYear = (int) date('Y', strtotime($earliestDate));
            }

            return response()->json([
                'success' => true,
                'earliest_year' => $earliestYear
            ]);
        } catch (\Exception $e) {
            // On error, default to current year
            return response()->json([
                'success' => true,
                'earliest_year' => (int) date('Y')
            ]);
        }
    }
}
