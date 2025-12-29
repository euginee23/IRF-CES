<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Restock Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    <tr>
                        <td style="padding: 0;">
                            {{-- Professional Header with Brand --}}
                            <div style="text-align: center; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 32px 20px; margin-bottom: 32px;">
                                <div style="font-size: 32px; font-weight: 800; color: #ffffff; margin-bottom: 8px; letter-spacing: -0.5px;">
                                    {{ config('app.name') }}
                                </div>
                                <div style="font-size: 13px; color: #dbeafe; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">
                                    Intelligent Repair Flow & Client Engagement System
                                </div>
                            </div>

                            <div style="padding: 0 40px 40px 40px;">
                                <div style="font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 8px;">
                                    Restock Request
                                </div>

                                <div style="font-size: 15px; color: #6b7280; line-height: 1.6; margin-bottom: 28px;">
                                    Hello <strong style="color: #1f2937;">{{ $supplier->contact_person ?: $supplier->name }}</strong>,<br>
                                    We need to restock the following item. Please confirm availability and delivery timeline.
                                </div>

                                {{-- Part Details Card --}}
                                <div style="background: transparent; border: 2px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                                    <div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #3b82f6;">
                                        Part Information
                                    </div>

                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="padding: 10px 0; font-size: 13px; color: #6b7280; font-weight: 600; width: 40%;">PART NAME</td>
                                            <td style="padding: 10px 0; font-size: 15px; color: #111827; font-weight: 600;">{{ $part->name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 0; font-size: 13px; color: #6b7280; font-weight: 600; border-top: 1px solid #f3f4f6;">SKU</td>
                                            <td style="padding: 10px 0; font-size: 14px; color: #374151; font-family: monospace; border-top: 1px solid #f3f4f6;">{{ $part->sku }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 0; font-size: 13px; color: #6b7280; font-weight: 600; border-top: 1px solid #f3f4f6;">CATEGORY</td>
                                            <td style="padding: 10px 0; font-size: 14px; color: #374151; border-top: 1px solid #f3f4f6;">{{ $part->category }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 0; font-size: 13px; color: #6b7280; font-weight: 600; border-top: 1px solid #f3f4f6;">MANUFACTURER</td>
                                            <td style="padding: 10px 0; font-size: 14px; color: #374151; border-top: 1px solid #f3f4f6;">{{ $part->manufacturer }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 0; font-size: 13px; color: #6b7280; font-weight: 600; border-top: 1px solid #f3f4f6;">MODEL</td>
                                            <td style="padding: 10px 0; font-size: 14px; color: #374151; border-top: 1px solid #f3f4f6;">{{ $part->model }}</td>
                                        </tr>
                                    </table>
                                </div>

                                {{-- Additional Notes --}}
                                @if($part->description)
                                <div style="background: #f9fafb; border-left: 4px solid #3b82f6; padding: 16px 20px; margin-bottom: 24px; border-radius: 6px;">
                                    <div style="font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Part Description</div>
                                    <div style="font-size: 14px; color: #374151; line-height: 1.6;">{{ $part->description }}</div>
                                </div>
                                @endif

                                {{-- Additional Notes from Request --}}
                                @if($additionalNotes)
                                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px 20px; margin-bottom: 24px; border-radius: 6px;">
                                    <div style="font-size: 12px; color: #92400e; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Special Instructions</div>
                                    <div style="font-size: 14px; color: #78350f; line-height: 1.6; white-space: pre-line;">{{ $additionalNotes }}</div>
                                </div>
                                @endif

                                <div style="text-align: center; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                                    <div style="font-size: 12px; color: #9ca3af;">
                                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
