{{--
    Official receipt for one payment.

    Deliberately not the job order invoice: that shows what the repair costs,
    this shows what was handed over and what is left. A customer paying a
    deposit gets one of these now and another on collection.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18181b; margin: 0; padding: 32px; }
        .header { border-bottom: 2px solid #18181b; padding-bottom: 14px; margin-bottom: 20px; }
        .shop { font-size: 20px; font-weight: bold; }
        .doc { float: right; text-align: right; }
        .doc .title { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .doc .number { font-size: 13px; margin-top: 2px; }
        .clear { clear: both; }
        .meta { width: 100%; margin-bottom: 22px; }
        .meta td { vertical-align: top; padding: 0; width: 50%; }
        .label { color: #71717a; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 12px; margin-bottom: 8px; }
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.lines th { text-align: left; border-bottom: 1px solid #d4d4d8; padding: 7px 0; font-size: 10px; text-transform: uppercase; color: #71717a; }
        table.lines td { padding: 9px 0; border-bottom: 1px solid #f4f4f5; }
        .right { text-align: right; }
        .totals { width: 260px; float: right; }
        .totals td { padding: 5px 0; }
        .totals .grand td { border-top: 2px solid #18181b; font-weight: bold; font-size: 14px; padding-top: 9px; }
        .balance { color: #b91c1c; font-weight: bold; }
        .settled { color: #15803d; font-weight: bold; }
        .footer { margin-top: 48px; border-top: 1px solid #e4e4e7; padding-top: 12px; color: #71717a; font-size: 10px; text-align: center; }
        .void { color: #b91c1c; border: 2px solid #b91c1c; padding: 5px 10px; display: inline-block; font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="header">
    <div class="doc">
        <div class="title">OFFICIAL RECEIPT</div>
        <div class="number">{{ $payment->receipt_number }}</div>
        @if($payment->isVoided())
            <div style="margin-top:6px"><span class="void">VOID</span></div>
        @endif
    </div>
    <div class="shop">{{ config('app.name') }}</div>
    <div style="color:#71717a">Repair &amp; Service Centre</div>
    <div class="clear"></div>
</div>

<table class="meta">
    <tr>
        <td>
            <div class="label">Received from</div>
            <div class="value">{{ $jobOrder->customer_name }}</div>

            <div class="label">Contact</div>
            <div class="value">{{ $jobOrder->customer_phone }}</div>
        </td>
        <td>
            <div class="label">Job order</div>
            <div class="value">{{ $jobOrder->job_order_number }}</div>

            <div class="label">Device</div>
            <div class="value">{{ $jobOrder->device_brand }} {{ $jobOrder->device_model }}</div>

            <div class="label">Date paid</div>
            <div class="value">{{ $payment->paid_at->format('d M Y, g:ia') }}</div>
        </td>
    </tr>
</table>

<table class="lines">
    <thead>
        <tr>
            <th>Description</th>
            <th>Method</th>
            <th class="right">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                Payment towards repair {{ $jobOrder->job_order_number }}
                @if($payment->note)
                    <br><span style="color:#71717a;font-size:11px">{{ $payment->note }}</span>
                @endif
            </td>
            <td>
                {{ $payment->method->label() }}
                @if($payment->reference_no)
                    <br><span style="color:#71717a;font-size:11px">Ref: {{ $payment->reference_no }}</span>
                @endif
            </td>
            <td class="right">₱{{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>Total billed</td>
        <td class="right">₱{{ number_format($jobOrder->amountDue(), 2) }}</td>
    </tr>
    <tr>
        <td>Paid to date</td>
        <td class="right">₱{{ number_format($jobOrder->amountPaid(), 2) }}</td>
    </tr>
    <tr class="grand">
        <td>{{ $jobOrder->balance() > 0 ? 'Balance due' : 'Settled' }}</td>
        <td class="right {{ $jobOrder->balance() > 0 ? 'balance' : 'settled' }}">
            ₱{{ number_format($jobOrder->balance(), 2) }}
        </td>
    </tr>
</table>
<div class="clear"></div>

<div class="footer">
    Received by {{ $payment->receivedBy?->name ?? 'the shop' }}.
    @if($jobOrder->balance() > 0)
        This is a partial payment; the balance above is due on collection.
    @else
        Thank you — this repair is paid in full.
    @endif
</div>

</body>
</html>
