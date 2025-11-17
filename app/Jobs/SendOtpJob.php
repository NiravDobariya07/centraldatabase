<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use App\Constants\AppConstants;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email, $otp;
    public function __construct($email, $otp)
    {
        $this->email = $email;
        $this->otp = $otp;
    }

    public function handle()
    {
        try {
            Mail::to($this->email)->send(new TwoFactorCodeMail($this->otp));

            customLog(AppConstants::LOG_CATEGORIES['EVENTS'], "✅ OTP email sent.", [
                'email' => $this->email,
            ]);
        } catch (\Exception $e) {
            customLog(AppConstants::LOG_CATEGORIES['ERRORS'], "❌ Failed to send OTP email.", [
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
