<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Your Repair Quote</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
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
        .alert-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .alert-box strong {
            color: #1e40af;
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
            color: #3b82f6;
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
        .quote-price {
            background-color: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }
        .quote-price .label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .quote-price .amount {
            font-size: 36px;
            font-weight: 800;
            color: #16a34a;
        }
        .quote-notes {
            background-color: #fefce8;
            border-left: 4px solid #eab308;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #713f12;
        }
        .cta-button {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
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
            .quote-price .amount { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <h1>Repair Quote</h1>
                <p>{{ $quoteRequest->manufacturer }} {{ $quoteRequest->model }}</p>
            </div>

            <div class="content">
                <div class="alert-box">
                    <strong>Hello {{ $quoteRequest->name }},</strong><br>
                    We've reviewed your repair request and prepared a quote for your device. Please review the details below.
                </div>

                <div class="section">
                    <div class="section-title">Device Information</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Manufacturer:</div>
                            <div class="info-value">{{ $quoteRequest->manufacturer }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Model:</div>
                            <div class="info-value">{{ $quoteRequest->model }}</div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Issue Description</div>
                    <p style="color: #333; font-size: 14px; line-height: 1.6;">{{ $quoteRequest->issue_description }}</p>
                </div>

                <div class="quote-price">
                    <div class="label">Estimated Repair Cost</div>
                    <div class="amount">₱{{ number_format($quoteRequest->quoted_price, 2) }}</div>
                </div>

                @if($quoteRequest->quote_notes)
                    <div class="quote-notes">
                        <strong>Notes from our technician:</strong><br>
                        {{ $quoteRequest->quote_notes }}
                    </div>
                @endif

                <div class="button-container">
                    <a href="{{ $quoteRequest->portal_url }}" class="cta-button">
                        View Quote & Respond Online
                    </a>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        Or copy this link: <a href="{{ $quoteRequest->portal_url }}" style="color: #3b82f6; word-break: break-all;">{{ $quoteRequest->portal_url }}</a>
                    </p>
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
