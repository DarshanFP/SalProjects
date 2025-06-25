<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->from('passwordReset@salprojects.org', 'System Administrator, SalProjects')
            ->subject('Reset Your Password - SalProjects')
            ->view('emails.password-reset', [
                'resetUrl' => $resetUrl,
                'user' => $notifiable
            ]);
    }
}
