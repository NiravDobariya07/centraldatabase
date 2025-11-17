<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TwoFactorCode;
use Illuminate\Support\Facades\Log;
use App\Constants\AppConstants;

class DeleteExpiredTwoFactorCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:delete-expired';  // The command signature

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired two-factor authentication codes';  // Description of the command

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        $now = now();

        // Directly delete expired OTP codes without retrieving them first
        $deletedCount = TwoFactorCode::where('expires_at', '<', $now)->delete();

        $message = "🔒 No expired two-factor codes found.";
        if ($deletedCount) {
            $message = "🔒 Deleted {$deletedCount} expired two-factor codes. ✅";
        }

        $this->info($message);
        customLog(AppConstants::LOG_CATEGORIES['EVENTS'], $message);
    }
}
