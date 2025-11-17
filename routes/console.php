<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\DeleteExpiredTwoFactorCodes;
use App\Models\Lead;

// Register scheduled commands only if DB is connected
try {
    DB::connection()->getPdo();

    // If DB is connected, schedule the commands
    Schedule::command('otp:delete-expired')->daily();

    // $queues = config('queue.workers'); // Fetch the list of queue worker configurations from the config
    // foreach ($queues as $queue) {
    //     // Schedule a queue:work command for each configured queue
    //     Schedule::command("queue:work --queue={$queue['name']} --tries=1 --stop-when-empty --memory={$queue['memory']} --timeout={$queue['timeout']}")
    //         ->everyMinute()             // Run the worker every minute
    //         ->withoutOverlapping();     // Prevent overlapping runs to avoid duplicate processing
    // }

    Schedule::command('run:schedule-export')->everyMinute();

} catch (\Exception $e) {
    reportException($e, "🚫 DB connection failed. Skipping scheduled tasks", false);
}

// Artisan::command('store-search-vectors', function () {
//     $updatedCount = 0;
//     Lead::chunk(10000, function ($leads) use (&$updatedCount) {
//         foreach ($leads as $lead) {
//             $fields = [
//                 'first_name', 'last_name', 'email', 'phone', 'alt_phone', 'address', 'city', 
//                 'state', 'postal', 'country', 'list_id', 'lead_id', 'jornaya_id', 'trusted_form_id', 
//                 'tax_debt_amount', 'cc_debt_amount', 'type_of_debt', 'home_owner', 'offer_url', 
//                 'page_url', 'source_site', 'sub_id_1', 'sub_id_2', 'sub_id_3', 'sub_id_4', 
//                 'sub_id_5', 'aff_id_1', 'aff_id_2', 'ef_id', 'ck_id'
//             ];

//             // Generate the search vector
//             $lead->search_vector = implode(' ', array_filter(
//                 array_map(fn($field) => trim($lead->$field ?? ''), $fields),
//                 fn($value) => !empty($value)
//             ));

//             $lead->save();
//             $updatedCount++;
//         }
//         $this->info("Successfully updated $updatedCount leads.");
//     });
// })->purpose('Display an inspiring quote');