<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $messageSubject }}</title>
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
            padding: 32px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.35;
        }
        .content {
            padding: 32px 30px;
            color: #1f2937;
            font-size: 15px;
            line-height: 1.7;
        }
        /* Staff type plain text, so newlines are what separate paragraphs. */
        .message-body {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            padding: 20px 30px 28px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.6;
            border-top: 1px solid #e5e7eb;
        }
        @media only screen and (max-width: 600px) {
            .header, .content { padding-left: 20px; padding-right: 20px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <h1>{{ $messageSubject }}</h1>
            </div>

            <div class="content">
                {{-- Escaped, then rendered with pre-wrap: staff compose plain
                     text, and treating it as HTML would let a stray angle
                     bracket break the layout. --}}
                <div class="message-body">{{ $messageBody }}</div>
            </div>

            <div class="footer">
                {{ config('app.name') }}<br>
                This message was sent to you by our service team. Please reply to this email if you need anything further.
            </div>
        </div>
    </div>
</body>
</html>
