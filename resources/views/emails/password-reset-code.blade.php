<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Password Reset Code</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f5f5f5;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 30px 20px;
            text-align: center;
        }
        .code-box {
            background-color: #f3f4f6;
            border: 2px dashed #6366f1;
            border-radius: 12px;
            padding: 30px;
            margin: 25px 0;
        }
        .code-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }
        .code {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 12px;
            color: #4f46e5;
            font-family: 'Courier New', monospace;
        }
        .expiry-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: left;
            font-size: 13px;
            color: #92400e;
        }
        .security-note {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: left;
            font-size: 13px;
            color: #991b1b;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            color: #666;
            font-size: 13px;
        }
        .footer p {
            margin: 5px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-container { border-radius: 0; }
            .header { padding: 20px 15px; }
            .content { padding: 20px 15px; }
            .code { font-size: 36px; letter-spacing: 8px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <h1>Password Reset</h1>
                <p>{{ config('app.name') }}</p>
            </div>

            <div class="content">
                <p style="font-size: 16px; color: #333; margin-bottom: 5px;">Your verification code is:</p>

                <div class="code-box">
                    <div class="code-label">Enter this code to reset your password</div>
                    <div class="code">{{ $code }}</div>
                </div>

                <div class="expiry-notice">
                    <strong>This code expires in 10 minutes.</strong> If you need a new code, return to the forgot password page and request another one.
                </div>

                <div class="security-note">
                    <strong>Didn't request this?</strong> If you did not request a password reset, please ignore this email. Your account is safe.
                </div>
            </div>

            <div class="footer">
                <p><strong>{{ config('app.name') }}</strong></p>
                <p>Intelligent Repair Flow & Client Engagement System</p>
                <p style="margin-top: 10px; font-size: 11px; color: #999;">This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
