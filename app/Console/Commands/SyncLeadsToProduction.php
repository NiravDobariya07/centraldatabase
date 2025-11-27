<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AllContact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncLeadsToProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync-leads-to-production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync leads from local system to production system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ⏱️ Unlimited execution time
        set_time_limit(0);

        // 🧠 Increase memory limit
        ini_set('memory_limit', '-1');

        // 🔢 Hardcoded starting ID
        $lastInsertedId = 0; // Change to your desired starting point

        if (empty($lastInsertedId)) {
            $errorMessage = "❌ Lead sync aborted: Starting ID is not provided or is set to 0.";
            Log::channel('lead_sync')->error($errorMessage);
            dd($errorMessage);
        }

        $this->info("🔄 Initiating lead sync from leads with ID > {$lastInsertedId}...");
        Log::channel('lead_sync')->info("Lead sync started from ID > {$lastInsertedId}");

        AllContact::where('id', '>', $lastInsertedId)
            ->orderBy('id')
            ->chunkById(10000, function ($leadsChunk) {
                foreach ($leadsChunk as $lead) {
                    Log::channel('lead_sync')->info("🔄 Starting sync for Lead ID: {$lead->id}", [
                        'lead_data' => $lead->toArray(),
                    ]);

                    $response = $this->syncLeadToProduction($lead);

                    if ($response->successful()) {
                        $json = $response->json();

                        if (is_null($json)) {
                            Log::channel('lead_sync')->error("❌ Sync failed for Lead ID: {$lead->id}. API Response Is Null: ", [
                                'api_response' => $json,
                            ]);
                            dd('Error: Response is not valid JSON', $response->body());
                        }

                        if (!empty($json['status']) && ($json['status'] == "success")) {
                            Log::channel('lead_sync')->info("✅ Sync success for Lead ID: {$lead->id}. API Response: ", [
                                'api_response' => $json,
                            ]);
                            // $lead->delete(); // Uncomment if needed
                        } else {
                            Log::channel('lead_sync')->error("❌ Sync failed for Lead ID: {$lead->id}. API Response: ", [
                                'api_response' => $json,
                            ]);
                            dd('Sync failed. Response:', $json);
                        }
                    } else {
                        Log::channel('lead_sync')->error("❌ Failed to sync Lead ID {$lead->id}. Response: " . $response->body());
                        dd("Failed to sync Lead ID {$lead->id}. Response: " . $response->body());
                    }
                }
            });

        $this->info('✅ Sync process completed.');
    }

    /**
     * Sync individual lead to the production system.
     *
     * @param Lead $lead
     * @return \Illuminate\Http\Client\Response
     */
    protected function syncLeadToProduction(AllContact $lead)
    {

        $postData = [
            "First_Name"        => $lead->first_name,
            "Last_Name"         => $lead->last_name,
            "Email"             => $lead->email,
            "Phone"             => $lead->phone,
            "Alt_Phone"         => $lead->alt_phone,
            "Address"           => $lead->address,
            "City"              => $lead->city,
            "State"             => $lead->state,
            "Postal"            => $lead->postal,
            "Country"           => $lead->country,
            "IP"                => $lead->ip,
            // "Date_Subscribed"   => $lead->date_subscribed,
            "Gender"            => $lead->gender,
            "Offer_URL"         => $lead->offer_url,
            // "DOB"               => $lead->dob,
            "List_ID"           => !empty($lead->campaign_list_data->list_id) ? $lead->campaign_list_data->list_id : null,
            // "Import_Date"       => $lead->import_date,
            "Phone_Type"        => $lead->phone_type,
            "Tax_Debt_Amount"   => $lead->tax_debt_amount,
            "CC_Debt_Amount"    => $lead->cc_debt_amount,
            "Type_Of_Debt"      => $lead->type_of_debt,
            "Homeowner"         => $lead->home_owner,
            "Jornaya_ID"        => $lead->jornaya_id,
            "Trusted_Form_ID"   => $lead->trusted_form_id,
            "Opt_In"            => $lead->opt_in,
            "SubID1"            => $lead->sub_id_1,
            "SubID2"            => $lead->sub_id_2,
            "SubID3"            => $lead->sub_id_3,
            "SubID4"            => $lead->sub_id_4,
            "SubID5"            => $lead->sub_id_5,
            "Aff_ID_1"          => $lead->aff_id_1,
            "Aff_ID_2"          => $lead->aff_id_2,
            "Lead_ID"           => $lead->lead_id,
            "Page_URL"          => $lead->page_url,
            "EF_ID"             => $lead->ef_id,
            "CK_ID"             => $lead->ck_id,
        ];

        if (!empty($lead->date_subscribed) && strtotime($lead->date_subscribed) !== false) {
            $postData['Date_Subscribed'] = \Carbon\Carbon::parse($lead->date_subscribed)->format('Y-m-d H:i:s');
        } else {
            $postData['Date_Subscribed'] = null;
        }

        if (!empty($lead->dob) && strtotime($lead->dob) !== false) {
            $postData['DOB'] = \Carbon\Carbon::parse($lead->dob)->format('Y-m-d');
        } else {
            $postData['DOB'] = null;
        }

        if (!empty($lead->import_date) && strtotime($lead->import_date) !== false) {
            $postData['Import_Date'] = \Carbon\Carbon::parse($lead->import_date)->format('Y-m-d H:i:s');
        } else {
            $postData['Import_Date'] = null;
        }

        if (!empty($lead->extra_fields) && is_array($lead->extra_fields)) {
            $postData = array_merge($postData, $lead->extra_fields);
        }

        // ✅ Log the post data
        Log::channel('lead_sync')->info('Syncing lead ID: ' . $lead->id, $postData);

        // Sending lead data to the production API using Laravel's HTTP client
        return Http::post('https://tradb.com/api/listflex-webhook', $postData);
    }
}
