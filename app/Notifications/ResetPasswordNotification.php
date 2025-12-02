<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expiryTime = Config::get('auth.passwords.users.expire', 60); // Default 60 minutes

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.password_reset', [
                'name' => $notifiable->name,
                'reset_url' => $resetUrl,
                'expiry_time' => $expiryTime,
            ]);
    }

    /**
     * Get the reset URL for the given notifiable.
     */
    protected function resetUrl($notifiable)
    {
        // Encrypt the token and email together
        $encryptedData = Crypt::encrypt([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        // URL encode the encrypted data for safe URL transmission
        $encryptedParam = urlencode($encryptedData);

        // Create signed URL with encrypted data
        return URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(Config::get('auth.passwords.users.expire', 60)),
            ['encrypted' => $encryptedParam]
        );
    }
}

