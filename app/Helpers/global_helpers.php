<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\LogLevel;
use App\Jobs\SendErrorReportJob;
use App\Constants\AppConstants;
use Illuminate\Support\Facades\Mail;
use App\Mail\ErrorReportMail;

/**
 * Get active log level dynamically from cache
 */
if (!function_exists('getActiveLogLevel')) {
    function getActiveLogLevel(): ?LogLevel {
        return Cache::rememberForever('active_log_level', function () {
            return LogLevel::where('enabled', true)->first();
        });
    }
}

/**
 * Get active log categories dynamically
 */
if (!function_exists('getActiveLogCategories')) {
    function getActiveLogCategories(): array {
        $activeLevel = getActiveLogLevel();

        return $activeLevel && $activeLevel->logCategories
            ? $activeLevel->logCategories->pluck('name')->toArray()
            : [];
    }
}

/**
 * Common Logging Function
 * 
 * @param string $category Log category name
 * @param string $message Log message
 * @param array  $context Additional context data
 */
if (!function_exists('customLog')) {
    function customLog(string $category, string $message, array $context = [], $channel = 'daily'): void {
        $logLevels = config('logging.log_levels');
        $activeLogLevelKey = env('ACTIVE_LOG_LEVEL', 'LEVEL_3');

        // Check if the active log level exists in the config, otherwise use LEVEL_3
        $activeLogLevel = isset($logLevels[$activeLogLevelKey]) ? $logLevels[$activeLogLevelKey] : $logLevels['LEVEL_3'];

        // Log level information
        $logLevelInfo = $activeLogLevel ? "[Log Level: " . $activeLogLevel['name'] . "] " : "[Log Level: None] ";

        if (in_array($category, $activeLogLevel['categories'])) {
            switch ($category) {
                case 'errors':
                case 'exceptions':
                    Log::channel($channel)->error($logLevelInfo . $message, $context);
                    break;
                case 'events':
                    Log::channel($channel)->info($logLevelInfo . $message, $context);
                    break;
                case 'request':
                    Log::channel($channel)->debug($logLevelInfo . $message, $context);
                    break;
                case 'response':
                    Log::channel($channel)->debug($logLevelInfo . $message, $context);
                    break;
                default:
                    Log::channel($channel)->notice($logLevelInfo . $message, $context);
            }
        }
    }
}

/**
 * Reports an exception by logging it and optionally notifying via job or email.
 *
 * @param \Throwable $exception The caught exception or error.
 * @param string $contextMessage A context message to prepend to the error.
 * @param bool $useJob Whether to use a job to send email notification (default: true).
 * @return array The structured error details.
 */
function reportException(\Throwable $exception, string $contextMessage = '', bool $useJob = true, $channel = 'daily'): array {
    $errorDetails = [
        'message' => trim($contextMessage . ': ' . $exception->getMessage(), ': '),
        'file'    => $exception->getFile(),
        'line'    => $exception->getLine(),
        'trace'   => $exception->getTraceAsString(),
    ];

    // Log the error in the 'error_daily' channel
    Log::channel('error_daily')->error($errorDetails['message'], $errorDetails);
    customLog(AppConstants::LOG_CATEGORIES['EXCEPTIONS'], $errorDetails['message'], $errorDetails, $channel);

    if ($useJob) {
        SendErrorReportJob::dispatch($errorDetails)->onQueue(config('queue.queues.error_reporting_queue'));
    } else {
        $recipients = array_filter(array_map('trim', explode(',', env('DEVELOPER_EMAILS', ''))));

        if (!empty($recipients)) {
            Mail::to($recipients)->send(new ErrorReportMail($errorDetails));
            Log::channel($channel)->info('Error report email sent successfully.', [
                'recipients'   => $recipients,
                'errorDetails' => $errorDetails,
            ]);
        } else {
            Log::channel($channel)->warning('No recipients defined in DEVELOPER_EMAILS for error reporting.');
        }
    }

    return $errorDetails;
}

if (!function_exists('convertKeysUsingMapping')) {
    /**
     * Convert array keys based on a model-specific mapping and separate extra fields.
     *
     * @param  array  $array
     * @param  string  $modelName
     * @return array
     */
    function convertKeysUsingMapping(array $array, string $modelName)
    {
        // Fetch the key mappings for the model from the config file
        $keyMappings = config("key_mappings.{$modelName}");

        // If no mappings are found for the model, return the original array along with an empty extra_fields array
        if (!$keyMappings) {
            return ['mapped_data' => $array, 'extra_fields' => []];
        }

        // Initialize arrays for mapped data and extra fields
        $mappedData = [];
        $extraFields = [];

        // Loop through each item in the array
        foreach ($array as $key => $value) {
            // If the key exists in the mapping, use the mapped key, otherwise store it in extra fields
            if (isset($keyMappings[$key])) {
                $mappedData[$keyMappings[$key]] = $value;
            } else {
                $extraFields[$key] = $value;
            }
        }

        // Return both mapped data and extra fields as separate arrays
        return [
            'mapped_data' => $mappedData,
            'extra_fields' => $extraFields,
        ];
    }
}

/**
 * Get the current version from the environment or generate a random version.
 *
 * @return string The current version or a random 4-digit version.
 */
function currentVersion() {
    // Retrieve the current version from the 'CURRENT_VERSION' environment variable, defaulting to null if not set
    $currentVersion = env('CURRENT_VERSION', null);

    // If no version is set, generate a random 4-digit version, padded with zeros if necessary
    return $currentVersion ?? str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

function formatCurrency($amount) {
    return is_numeric($amount) ? '$' . number_format($amount, ($amount == floor($amount) ? 0 : 2)) : $amount;
}

function formatString($inputString) {
    return preg_replace('/_/', ' ', ucfirst($inputString));
}

function formatStringWithNewLines($inputString, $breakAfter = 5) {
    $items = explode(',', $inputString); // Convert string to an array
    $formattedString = '';
    $counter = 0;

    foreach ($items as $item) {
        $formattedString .= trim($item) . ', '; // Trim and append value
        $counter++;

        if ($counter % $breakAfter == 0) {
            $formattedString .= "\n"; // Add a new line after every $breakAfter elements
        }
    }

    return rtrim($formattedString, ", \n"); // Trim trailing comma and newline
}

/**
 * Recursively sanitize data by converting all strings to valid UTF-8.
 *
 * @param mixed $data
 * @return mixed
 */
function sanitizeDataForUtf8($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeDataForUtf8($value);
        }
        return $data;
    } elseif (is_string($data)) {
        if (!mb_check_encoding($data, 'UTF-8')) {
            // Log the invalid string
            Log::warning('Invalid UTF-8 detected in payload');
        }
        // Convert to valid UTF-8, removing invalid characters
        return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    }

    return $data;
}

function getLeadKeyByValue(string $value): string {
    $mapping = config('key_mappings.Lead');

    return array_search($value, $mapping, true) ?: $value;
}