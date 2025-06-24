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
        return (new MailMessage)
            ->from('passwordReset@salprojects.org', 'System Administrator, SalProjects')
            ->subject('Reset Your Password - SalProjects')
            ->markdown('vendor.notifications.email', [
                'greeting' => 'Hello from SalProjects!',
                'introLines' => [
                    'You are receiving this email because we received a password reset request for your account.',
                ],
                'actionText' => 'Reset Password',
                'actionUrl' => url(route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)),
                'outroLines' => [
                    'If you did not request a password reset, no further action is required.',
                ],
                'salutation' => 'Regards,<br>System Administrator, SalProjects',
                'level' => 'primary',
                'displayableActionUrl' => url(route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)),
            ]);
    }
}
