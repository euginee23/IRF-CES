<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name') }} - Restock Request</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 40px 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            text-align: center;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 32px 20px;
        }
        .header-title {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }
        .header-subtitle {
            font-size: 13px;
            color: #dbeafe;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .content {
            padding: 40px 40px 40px 40px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 8px 0;
        }
        .greeting {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 28px 0;
        }
        .card {
            background: transparent;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #3b82f6;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 10px 10px 10px 0;
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
            width: 40%;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            padding: 10px 0;
            font-size: 15px;
            color: #111827;
            font-weight: 600;
        }
        .info-value-mono {
            font-family: monospace;
            font-size: 14px;
            color: #374151;
            font-weight: 400;
        }
        .info-value-normal {
            font-size: 14px;
            color: #374151;
            font-weight: 400;
        }
        .note-box {
            background: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 6px;
        }
        .note-box-warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        .note-title {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .note-title-warning {
            color: #92400e;
        }
        .note-content {
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
            margin: 0;
            white-space: pre-line;
        }
        .note-content-warning {
            color: #78350f;
        }
        .footer {
            text-align: center;
            padding: 24px 20px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 12px;
            color: #9ca3af;
            margin: 0;
        }
        .divider {
            border-top: 1px solid #f3f4f6;
        }

        /* Mobile Responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px 0;
            }
            .email-container {
                border-radius: 0;
            }
            .header {
                padding: 24px 15px;
            }
            .header-title {
                font-size: 24px;
            }
            .header-subtitle {
                font-size: 11px;
            }
            .content {
                padding: 25px 20px;
            }
            .page-title {
                font-size: 20px;
            }
            .greeting {
                font-size: 14px;
                margin-bottom: 20px;
            }
            .card {
                padding: 16px;
                margin-bottom: 16px;
            }
            .card-title {
                font-size: 16px;
                margin-bottom: 12px;
                padding-bottom: 10px;
            }
            .info-label,
            .info-value {
                display: block;
                width: 100%;
                padding: 4px 0;
            }
            .info-label {
                font-size: 11px;
                color: #9ca3af;
                margin-bottom: 2px;
            }
            .info-value {
                font-size: 14px;
                margin-bottom: 12px;
            }
            .info-value-mono,
            .info-value-normal {
                font-size: 13px;
            }
            .note-box {
                padding: 12px 15px;
                margin-bottom: 16px;
            }
            .note-title {
                font-size: 11px;
            }
            .note-content {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <div class="header-title">{{ config('app.name') }}</div>
                <div class="header-subtitle">Intelligent Repair Flow & Client Engagement System</div>
            </div>

            <div class="content">
                <div class="page-title">Restock Request</div>

                <div class="greeting">
                    Hello <strong style="color: #1f2937;">{{ $supplier->contact_person ?: $supplier->name }}</strong>,<br>
                    We need to restock the following item. Please confirm availability and delivery timeline.
                </div>

                <div class="card">
                    <div class="card-title">Part Information</div>
                    <table class="info-table">
                        <tr class="info-row">
                            <td class="info-label">PART NAME</td>
                            <td class="info-value">{{ $part->name }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label divider">SKU</td>
                            <td class="info-value info-value-mono divider">{{ $part->sku }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label divider">CATEGORY</td>
                            <td class="info-value info-value-normal divider">{{ $part->category }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label divider">MANUFACTURER</td>
                            <td class="info-value info-value-normal divider">{{ $part->manufacturer }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label divider">MODEL</td>
                            <td class="info-value info-value-normal divider">{{ $part->model }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label divider">REQUESTED QUANTITY</td>
                            <td class="info-value divider" style="color: #3b82f6;">{{ $requestedQuantity }}</td>
                        </tr>
                    </table>
                </div>

                @if($part->description)
                <div class="note-box">
                    <div class="note-title">Part Description</div>
                    <div class="note-content">{{ $part->description }}</div>
                </div>
                @endif

                @if($additionalNotes)
                <div class="note-box note-box-warning">
                    <div class="note-title note-title-warning">Special Instructions</div>
                    <div class="note-content note-content-warning">{{ $additionalNotes }}</div>
                </div>
                @endif

                <div class="footer">
                    <div class="footer-text">
                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
