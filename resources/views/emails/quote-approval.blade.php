<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Quote Approval Request</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
        }
        .alert-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .alert-box strong {
            color: #92400e;
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
            color: #667eea;
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
        .parts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .parts-table th {
            background-color: #f8f9fa;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            border-bottom: 2px solid #e5e5e5;
            text-transform: uppercase;
        }
        .parts-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e5e5;
            font-size: 14px;
        }
        .parts-table td:nth-child(2),
        .parts-table th:nth-child(2) {
            text-align: center;
        }
        .parts-table td:nth-child(3),
        .parts-table td:nth-child(4),
        .parts-table th:nth-child(3),
        .parts-table th:nth-child(4) {
            text-align: right;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .total-row.grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #10b981;
            padding-top: 12px;
            border-top: 2px solid #e5e5e5;
            margin-top: 10px;
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
        .services-list {
            margin: 0;
            padding-left: 20px;
        }
        .services-list li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .services-list strong {
            color: #333;
        }
        .services-list span {
            color: #666;
            font-size: 13px;
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
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        /* Mobile Responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px 0;
            }
            .email-container {
                border-radius: 0;
                margin: 0;
            }
            .header {
                padding: 20px 15px;
            }
            .header h1 {
                font-size: 20px;
            }
            .header p {
                font-size: 12px;
            }
            .content {
                padding: 20px 15px;
            }
            .section-title {
                font-size: 14px;
            }
            .info-label,
            .info-value {
                display: block;
                width: 100%;
                padding: 4px 0;
            }
            .info-label {
                font-size: 11px;
                color: #999;
                margin-bottom: 2px;
            }
            .info-value {
                font-size: 13px;
                margin-bottom: 12px;
            }
            .parts-table {
                font-size: 12px;
            }
            .parts-table th,
            .parts-table td {
                padding: 8px 5px;
            }
            .total-row {
                font-size: 14px;
            }
            .total-row.grand-total {
                font-size: 18px;
            }
            .alert-box {
                padding: 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <h1>Job Order Quote</h1>
                <p>{{ $jobOrder->job_order_number }}</p>
            </div>

            <div class="content">
                <div class="alert-box">
                    <strong>Action Required:</strong> Review your repair quote and approve it online using the button below.
                </div>

                <div class="button-container">
                    <a href="{{ $jobOrder->portal_url }}" class="cta-button">
                        View & Approve Quote Online
                    </a>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        Or copy this link: <a href="{{ $jobOrder->portal_url }}" style="color: #667eea; word-break: break-all;">{{ $jobOrder->portal_url }}</a>
                    </p>
                </div>

                <div class="section">
                    <div class="section-title">Customer Information</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Name:</div>
                            <div class="info-value">{{ $jobOrder->customer_name }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value">{{ $jobOrder->customer_phone }}</div>
                        </div>
                        @if($jobOrder->customer_email)
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value">{{ $jobOrder->customer_email }}</div>
                        </div>
                        @endif
                        @if($jobOrder->customer_address)
                        <div class="info-row">
                            <div class="info-label">Address:</div>
                            <div class="info-value">{{ $jobOrder->customer_address }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Device Information</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Brand:</div>
                            <div class="info-value">{{ $jobOrder->device_brand }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Model:</div>
                            <div class="info-value">{{ $jobOrder->device_model }}</div>
                        </div>
                        @if($jobOrder->serial_number)
                        <div class="info-row">
                            <div class="info-label">Serial/IMEI:</div>
                            <div class="info-value">{{ $jobOrder->serial_number }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Issue Description</div>
                    <p style="margin: 0; line-height: 1.6; color: #333;">{{ $jobOrder->issue_description }}</p>
                </div>

                @if($jobOrder->issues && count($jobOrder->issues) > 0)
                <div class="section">
                    <div class="section-title">Services Required</div>
                    <ul class="services-list">
                        @foreach($jobOrder->issues as $issue)
                            <li>
                                <strong>{{ $issue['type'] ?? 'N/A' }}</strong>
                                @if(!empty($issue['description']))
                                    <br><span>{{ $issue['description'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($jobOrder->parts_needed && count($jobOrder->parts_needed) > 0)
                <div class="section">
                    <div class="section-title">Parts Needed</div>
                    <table class="parts-table">
                        <thead>
                            <tr>
                                <th>Part Name</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobOrder->parts_needed as $part)
                            <tr>
                                <td>{{ $part['part_name'] ?? 'N/A' }}</td>
                                <td>{{ $part['quantity'] ?? 1 }}</td>
                                <td>₱{{ number_format($part['unit_sale_price'] ?? 0, 2) }}</td>
                                <td>₱{{ number_format(($part['quantity'] ?? 1) * ($part['unit_sale_price'] ?? 0), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="total-section">
                    <div class="total-row">
                        <span>Parts Total:</span>
                        <span>₱{{ number_format($partsTotal, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span>Labor Total:</span>
                        <span>₱{{ number_format($laborTotal, 2) }}</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Estimated Total:</span>
                        <span>₱{{ number_format($estimatedTotal, 2) }}</span>
                    </div>
                </div>

                @if($jobOrder->expected_completion_date)
                <div class="section" style="border: none; margin-top: 20px;">
                    <div class="section-title">Expected Completion</div>
                    <p style="font-size: 16px; font-weight: 600; margin: 0; color: #333;">{{ $jobOrder->expected_completion_date->format('F d, Y') }}</p>
                </div>
                @endif

                <div class="button-container">
                    <a href="{{ $jobOrder->portal_url }}" class="cta-button">View & Approve Quote Online</a>
                </div>

                <div class="alert-box" style="background-color: #dbeafe; border-left-color: #3b82f6; margin-top: 20px;">
                    <strong style="color: #1e40af;">Quick & Easy:</strong> Click the button above to view full details and approve your repair quote online in seconds. We'll begin work immediately once approved!
                </div>
            </div>

            <div class="footer">
                <p>Thank you for choosing our service!</p>
                <p style="color: #999; font-size: 12px;">This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
