<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ExportFile;
use App\Constants\AppConstants;
use Illuminate\Support\Facades\Log;

class ExportFileGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $exportFile;

    /**
     * Create a new message instance.
     */
    public function __construct(?ExportFile $exportFile)
    {
        if (empty($exportFile)) {
            // Handle the case: throw an exception or set a flag to use a fallback view
            throw new \Exception("Export file not found.");
        }
        $this->exportFile = $exportFile;
    }

    /**
     * Build the message.
     */
    // public function build()
    // {
    //     return $this->subject('Your Export File is Ready!')
    //                 ->view('emails.export_file_generated')
    //                 ->with([
    //                     'exportFile' => $this->exportFile
    //                 ]);
    // }

    public function build() {
        $subject = "Your Export File is Ready";
        if (!empty($this->exportFile->export->title)) {
            $title = $this->exportFile->export->title;
            $shortTitle = strlen($title) > 40 ? substr($title, 0, 37) . '...' : $title;
            $subject = "Your Export File is Ready: {$shortTitle}";
        }

        $logPrefix = "Export Id ({$this->exportFile->export_id}) : [Export Email Notification]";
        Log::channel('export_daily')->info(sprintf(
            "📧 {$logPrefix} Sending Export File Email for Export File ID: %s",
            $this->exportFile->id ?? 'N/A'
        ));
        return $this->subject($subject)
                    ->view('emails.export_file_generated')
                    ->with([
                        'exportFile' => $this->exportFile
                    ]);
    }
}