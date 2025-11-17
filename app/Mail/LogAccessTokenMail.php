<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class LogAccessTokenMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url, $expirationMinutes, $requestIp;

    /**
     * Create a new message instance.
     *
     * @param string $url
     * @param int $expirationMinutes
     * @return void
     */
    public function __construct(string $url, int $expirationMinutes, $requestIp = "")
    {
        $this->url = $url;
        $this->expirationMinutes = $expirationMinutes;
        $this->requestIp = $requestIp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $environment = env('APP_ENV');
        $date = Carbon::now()->format('l, jS M Y, h:i A');
        return new Envelope(
            subject: "TextLine [{$environment}] - Access Logs ({$date})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.log_access_token', // Update to your view path
            with: [
                'request_ip' => $this->requestIp,
                'url' => $this->url,
                'expirationMinutes' => $this->expirationMinutes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
