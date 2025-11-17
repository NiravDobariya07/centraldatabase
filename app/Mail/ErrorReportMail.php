<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ErrorReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailSubject, $errorDetails;

    /**
     * Create a new message instance.
     */
    public function __construct($errorDetails)
    {
        $formattedDate = Carbon::now()->format('l, F j, Y g:i:s A');
        $projectName = env('APP_NAME', 'Alleviate Tax');
        $this->emailSubject = "{$projectName}::Error Logs ({$formattedDate})";
        $this->errorDetails = $errorDetails;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->emailSubject)
                    ->view('emails.error_report')
                    ->with(['errorDetails' => $this->errorDetails]);
    }
}
