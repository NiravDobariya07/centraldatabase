<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Repositories\{
    LeadRepository,
    CampaignListIdRepository,
    SourceSiteRepository
};
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Constants\AppConstants;

class ProcessLeadData implements ShouldQueue
{
    use Queueable;
    protected $mappedPostData,$requestData, $leadRepository, $campaignListIdRepository, $sourceSiteRepository;

    /**
     * Create a new job instance.
     *
     * @param array $mappedPostData
     * @param LeadRepository $leadRepository
     * @return void
     */
    public function __construct(
        array $requestData
    ) {
        $this->requestData = $requestData;
        $this->mappedPostData = convertKeysUsingMapping($requestData, 'AllContact');
        $this->leadRepository = new LeadRepository();
        $this->campaignListIdRepository = new CampaignListIdRepository();
        $this->sourceSiteRepository = new SourceSiteRepository();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Extract mapped data
            $leadPostData                   = $this->mappedPostData['mapped_data'];
            $leadPostData['extra_fields']   = $this->mappedPostData['extra_fields'];

            // Extract email domain if email exists
            if (!empty($leadPostData['email'])) {
                $emailParts = explode('@', $leadPostData['email']);
                if (count($emailParts) === 2) {
                    $leadPostData['email_domain'] = $emailParts[1];
                }
            }

            // Extract optin domain from page_url if exists
            if (isset($leadPostData['page_url'])) {
                $url = parse_url($leadPostData['page_url']);
                $sourceDomain = !empty($url['host']) ? $url['host'] : $leadPostData['page_url'];
                if (!empty($sourceDomain)) {
                    $leadPostData['optin_domain'] = $sourceDomain;
                }
            }

            // Ip Address - map to ip_address field
            if (isset($leadPostData['ip_address'])) {
                $leadPostData['ip_address'] = filter_var($leadPostData['ip_address'], FILTER_VALIDATE_IP) ? $leadPostData['ip_address'] : null;
            } elseif (isset($leadPostData['ip'])) {
                $leadPostData['ip_address'] = filter_var($leadPostData['ip'], FILTER_VALIDATE_IP) ? $leadPostData['ip'] : null;
                unset($leadPostData['ip']);
            }

            // Date Fields - handle lead_time_stamp
            if (!empty($leadPostData['lead_time_stamp'])) {
                $leadPostData['lead_time_stamp'] = $this->parseDate($leadPostData['lead_time_stamp'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            } elseif (!empty($leadPostData['date_subscribed'])) {
                $leadPostData['lead_time_stamp'] = $this->parseDate($leadPostData['date_subscribed'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
                unset($leadPostData['date_subscribed']);
            }

            // Remove fields that don't exist in all_contacts table
            $fieldsToRemove = ['alt_phone', 'address', 'city', 'state', 'postal', 'country', 'date_subscribed',
                              'gender', 'offer_url', 'dob', 'import_date', 'phone_type', 'tax_debt_amount',
                              'cc_debt_amount', 'type_of_debt', 'home_owner', 'opt_in', 'sub_id_1', 'sub_id_2',
                              'sub_id_3', 'sub_id_4', 'sub_id_5', 'aff_id_1', 'aff_id_2', 'lead_id', 'page_url',
                              'ef_id', 'ck_id', 'source_site_id', 'campaign_list_id', 'extra_fields'];

            foreach ($fieldsToRemove as $field) {
                unset($leadPostData[$field]);
            }

            // Store the lead data in the repository
            $lead = $this->leadRepository->store($leadPostData);

            // Log request if the "request" category is enabled
            customLog(AppConstants::LOG_CATEGORIES['EVENTS'], "✅ Lead data successfully stored in the database. Lead Data : " . json_encode($lead->toArray()));

        } catch (\Exception $e) {
            reportException($e, "Error processing lead data");

            // You can optionally rethrow the error or handle it differently
            throw $e;
        }
    }

    /**
     * Parses date based on known formats and converts to a standardized format.
     *
     * @param string $dateString
     * @param string $type
     * @return string|null
     */
    private function parseDate($dateString, $currentFormat, $targetFormat)
    {
        try {
            if (empty($dateString) || $dateString === '0000-00-00') {
                return null;
            }

            // Validate that the date format matches before parsing
            if (!Carbon::hasFormat($dateString, $currentFormat)) {
                \Log::warning("Invalid date format: Expected {$currentFormat}, received {$dateString}");
                return null;
            }

            return Carbon::createFromFormat($currentFormat, $dateString)->format($targetFormat);
        } catch (\Exception $e) {
            Log::error("Error parsing Current Format: {$currentFormat} targetFormat: {$targetFormat} date: {$dateString}. Error: " . $e->getMessage());
            return null;
        }
    }
}
