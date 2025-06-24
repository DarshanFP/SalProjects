# Password Reset System Setup Guide

## Overview

The password reset system has been successfully implemented in your SalProjects application. Users can now request password reset links via email and reset their passwords securely.

## What's Already Implemented

### ✅ Completed Features:

1. **User Model**: Updated to implement `CanResetPassword` interface
2. **Routes**: All password reset routes are properly configured
3. **Controllers**: Password reset controllers are in place
4. **Views**: Styled password reset pages matching your NobleUI theme
5. **Database**: Password reset tokens table exists
6. **Login Page**: "Forgot your password?" link is properly connected

### 🔧 Routes Available:

-   `GET /forgot-password` - Password reset request form
-   `POST /forgot-password` - Send password reset email
-   `GET /reset-password/{token}` - Password reset form
-   `POST /reset-password` - Process password reset

## Email Configuration Setup

### Step 1: Configure Email Settings

You need to set up email configuration in your `.env` file. Here are the recommended settings:

#### For Gmail:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="SalProjects"
```

#### For Other SMTP Providers:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@domain.com
MAIL_FROM_NAME="SalProjects"
```

### Step 2: Gmail App Password Setup (if using Gmail)

1. Go to your Google Account settings
2. Enable 2-Factor Authentication
3. Generate an App Password for your application
4. Use this App Password in your `.env` file

### Step 3: Test Email Configuration

You can test your email configuration by running:

```bash
php artisan tinker
```

Then in tinker:

```php
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});
```

## How It Works

### 1. User Requests Password Reset

-   User clicks "Forgot your password?" on login page
-   User enters their email address
-   System validates email and sends reset link

### 2. Password Reset Email

-   User receives email with reset link
-   Link contains a secure token and user's email
-   Token expires after 60 minutes (configurable)

### 3. User Resets Password

-   User clicks link in email
-   User enters new password and confirmation
-   System validates token and updates password
-   User is redirected to login page

## Security Features

-   **Token Expiration**: Reset tokens expire after 60 minutes
-   **Throttling**: Users can only request reset links every 60 seconds
-   **Secure Tokens**: Each reset token is unique and cryptographically secure
-   **Email Validation**: Only registered email addresses can request resets

## Customization Options

### Change Token Expiration Time

Edit `config/auth.php`:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // Change this value (in minutes)
        'throttle' => 60, // Change this value (in seconds)
    ],
],
```

### Customize Email Template

The password reset email uses Laravel's default template. You can customize it by publishing the notification:

```bash
php artisan vendor:publish --tag=laravel-notifications
```

### Customize Email Content

Edit `app/Models/User.php` to customize the email:

```php
public function sendPasswordResetNotification($token)
{
    $this->notify(new ResetPassword($token));
}
```

## Troubleshooting

### Common Issues:

1. **Email not sending**:

    - Check SMTP settings in `.env`
    - Verify email credentials
    - Check firewall/network settings

2. **Reset link not working**:

    - Ensure APP_URL is set correctly in `.env`
    - Check if token has expired
    - Verify database connection

3. **"User not found" error**:
    - Ensure user exists with the provided email
    - Check if email is verified (if required)

### Debug Email Issues:

```bash
# Set mail driver to log for debugging
MAIL_MAILER=log
```

Then check `storage/logs/laravel.log` for email content.

## Testing the System

1. **Test Password Reset Flow**:

    - Go to `/forgot-password`
    - Enter a valid user email
    - Check if email is received
    - Click reset link and set new password

2. **Test Security**:
    - Try resetting with non-existent email
    - Try using expired token
    - Test throttling by requesting multiple resets

## Support

If you encounter any issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify email configuration
3. Ensure database is properly connected
4. Check if all routes are accessible

The password reset system is now fully functional and ready to use!
