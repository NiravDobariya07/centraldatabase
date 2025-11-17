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
        $this->mappedPostData = convertKeysUsingMapping($requestData, 'Lead');
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

            // Check if the page URL exists and extract domain
            if (isset($leadPostData['page_url'])) {
                $url = parse_url($leadPostData['page_url']);
                
                // Extract domain from the URL
                $sourceDomain = !empty($url['host']) ? $url['host'] : $leadPostData['page_url'];

                if (!empty($sourceDomain)) {
                    $sourceSite = $this->sourceSiteRepository->firstOrCreate(['domain'=> $sourceDomain]);
                    if (!empty($sourceSite)) {
                        $leadPostData['source_site_id'] = $sourceSite->id;
                    }
                }
            }

            if (!empty($leadPostData['list_id'])) {
                $campaignList = $this->campaignListIdRepository->firstOrCreate(['list_id'=> $leadPostData['list_id']]);
                if (!empty($campaignList)) {
                    $leadPostData['campaign_list_id'] = $campaignList->id;
                }
            }

            // Ip Address
            $leadPostData['ip'] = filter_var($leadPostData['ip'], FILTER_VALIDATE_IP) ? $leadPostData['ip'] : null;

            // Numeric Fields
            $leadPostData['tax_debt_amount'] = is_numeric(trim($leadPostData['tax_debt_amount'] ?? '')) ? $leadPostData['tax_debt_amount'] : null;
            $leadPostData['cc_debt_amount'] = is_numeric(trim($leadPostData['cc_debt_amount'] ?? '')) ? $leadPostData['cc_debt_amount'] : null;

            // Date Fields
            $leadPostData['date_subscribed']  = !empty($leadPostData['date_subscribed']) ? $this->parseDate($leadPostData['date_subscribed'], 'Y-m-d H:i:s', 'Y-m-d H:i:s') : null;
            $leadPostData['dob']              = !empty($leadPostData['dob']) ? $this->parseDate($leadPostData['dob'], 'Y-m-d', 'Y-m-d') : null;
            $leadPostData['import_date']      = !empty($leadPostData['import_date']) ? $this->parseDate($leadPostData['import_date'], 'Y-m-d H:i:s', 'Y-m-d H:i:s') : null;

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