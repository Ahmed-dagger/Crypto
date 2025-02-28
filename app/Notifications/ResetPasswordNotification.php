<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use \Illuminate\Bus\Queueable; // Ensure Queueable is imported

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail']; // Ensures email is sent
    }

    public function toMail($notifiable)
    {
        $appDeepLink = "yourapp://reset-password?token={$this->token}&email={$this->email}";
        $webLink = url("/password/reset/{$this->token}?email={$this->email}");

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting('Hello!')
            ->line('You requested a password reset. Click the button below to reset your password.')
            ->action('Reset Password', $appDeepLink) // Mobile app deep link
            ->line('Or you can use the following link:')
            ->line($webLink)
            ->line('If you did not request a password reset, no further action is required.');
    }
}
