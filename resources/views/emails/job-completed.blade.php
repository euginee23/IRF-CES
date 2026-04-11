<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Your Device is Ready for Pickup</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        }
        .success-box {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
            text-align: center;
        }
        .success-box .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .success-box h2 {
            color: #16a34a;
            font-size: 20px;
            margin: 0 0 8px 0;
        }
        .success-box p {
            color: #333;
            font-size: 14px;
            margin: 0;
        }
        .section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 8px 10px 8px 0;
            font-weight: 600;
            color: #666;
            font-size: 13px;
            width: 35%;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #333;
            font-size: 14px;
        }
        .pickup-notice {
            background-color: #fefce8;
            border: 2px solid #eab308;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }
        .pickup-notice strong {
            color: #a16207;
            font-size: 16px;
        }
        .pickup-notice p {
            color: #713f12;
            font-size: 14px;
            margin: 8px 0 0 0;
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
            .header h1 { font-size: 20px; }
            .content { padding: 20px 15px; }
            .info-label, .info-value { display: block; width: 100%; padding: 4px 0; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <h1>Repair Complete!</h1>
                <p>{{ $jobOrder->job_order_number }}</p>
            </div>

            <div class="content">
                <div class="success-box">
                    <div class="icon">&#10003;</div>
                    <h2>Your Device is Ready for Pickup</h2>
                    <p>Dear {{ $jobOrder->customer_name }}, your device repair has been completed successfully.</p>
                </div>

                <div class="section">
                    <div class="section-title">Job Order Details</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Job Order #:</div>
                            <div class="info-value"><strong>{{ $jobOrder->job_order_number }}</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Device:</div>
                            <div class="info-value">{{ $jobOrder->device_brand }} {{ $jobOrder->device_model }}</div>
                        </div>
                        @if($jobOrder->issue_description)
                        <div class="info-row">
                            <div class="info-label">Issue:</div>
                            <div class="info-value">{{ Str::limit($jobOrder->issue_description, 100) }}</div>
                        </div>
                        @endif
                        @if($jobOrder->completed_at)
                        <div class="info-row">
                            <div class="info-label">Completed:</div>
                            <div class="info-value">{{ $jobOrder->completed_at->format('M d, Y h:i A') }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="pickup-notice">
                    <strong>Please pick up your device at your earliest convenience.</strong>
                    <p>Bring a valid ID and your job order number for verification.</p>
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
