<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Your Password</title>
    <style>
        body        { margin: 0; padding: 0; background: #f4f4f5; font-family: Arial, sans-serif; }
        .wrapper    { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
        .header     { background: #7c3aed; padding: 32px 40px; }
        .header h1  { margin: 0; color: #ffffff; font-size: 22px; }
        .body       { padding: 40px; color: #374151; line-height: 1.6; }
        .body p     { margin: 0 0 16px; }
        .btn        { display: inline-block; background: #7c3aed; color: #ffffff !important;
                      text-decoration: none; padding: 14px 28px; border-radius: 6px;
                      font-weight: bold; font-size: 15px; margin: 8px 0 24px; }
        .footer     { padding: 24px 40px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .url-wrap   { word-break: break-all; color: #6b7280; font-size: 13px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Merchant Portal</h1>
        </div>
        <div class="body">
            <p>Hi {{ $firstName }},</p>
            <p>We received a request to reset the password for your Merchant account.
               Click the button below to choose a new password.</p>
            <p>
                <a href="{{ $resetUrl }}" class="btn">Reset My Password</a>
            </p>
            <p>This link will expire in <strong>60 minutes</strong>.</p>
            <p>If the button above does not work, copy and paste this URL into your browser:</p>
            <p class="url-wrap">{{ $resetUrl }}</p>
            <p>If you did not request a password reset, you can safely ignore this email.
               Your password will not change.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Auth System. All rights reserved.
        </div>
    </div>
</body>
</html>