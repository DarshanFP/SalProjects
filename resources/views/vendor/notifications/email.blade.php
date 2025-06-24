@php
    $logoUrl = asset('backend/assets/images/favicon.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'SalProjects Notification' }}</title>
    <style>
        body {
            background: #23272b;
            color: #fff;
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 480px;
            margin: 40px auto;
            background: #2b2b2b;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .email-header {
            background: #1a73e8;
            padding: 24px 0 12px 0;
            text-align: center;
        }
        .email-header img {
            width: 48px;
            height: 48px;
            margin-bottom: 8px;
        }
        .email-header h1 {
            color: #fff;
            font-size: 1.5rem;
            margin: 0;
            font-weight: 700;
        }
        .email-body {
            padding: 32px 24px 24px 24px;
        }
        .email-body h2 {
            color: #1a73e8;
            font-size: 1.2rem;
            margin-top: 0;
        }
        .email-btn {
            display: inline-block;
            background: #1a73e8;
            color: #fff !important;
            padding: 12px 32px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            margin: 24px 0;
            transition: background 0.2s;
        }
        .email-btn:hover {
            background: #155ab6;
        }
        .email-footer {
            background: #23272b;
            color: #b0b0b0;
            text-align: center;
            padding: 16px 0;
            font-size: 0.95rem;
        }
        .subcopy {
            margin-top: 32px;
            font-size: 0.95rem;
            color: #b0b0b0;
        }
        a {
            color: #1a73e8;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <img src="{{ $logoUrl }}" alt="SalProjects Logo">
            <h1>SalProjects</h1>
        </div>
        <div class="email-body">
            <h2>{{ $greeting ?? 'Hello!' }}</h2>
            @foreach ($introLines as $line)
                <p>{{ $line }}</p>
            @endforeach
            @isset($actionText)
                <p style="text-align:center;">
                    <a href="{{ $actionUrl }}" class="email-btn">{{ $actionText }}</a>
                </p>
            @endisset
            @foreach ($outroLines as $line)
                <p>{{ $line }}</p>
            @endforeach
            <p style="margin-top:32px;">{!! $salutation ?? 'Regards,<br>System Administrator, SalProjects' !!}</p>
            @isset($actionText)
                <div class="subcopy">
                    @lang('If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:', ['actionText' => $actionText])
                    <br>
                    <span class="break-all">{{ $displayableActionUrl }}</span>
                </div>
            @endisset
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} SalProjects. All rights reserved.
        </div>
    </div>
</body>
</html>
