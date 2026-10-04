<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip {{ $jobOrder->job_order_number }}</title>
    {{-- Self-contained on purpose: a thermal printer wants black on white at
         a fixed 80mm, none of the app's theme, and nothing that has to load
         before the print dialog opens. --}}
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; }
        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
        }
        .slip { width: 72mm; margin: 0 auto; padding: 4mm 0 6mm; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .small { font-size: 10px; }
        .shop { font-size: 15px; font-weight: bold; text-transform: uppercase; }
        .title { margin: 6px 0 2px; font-size: 13px; font-weight: bold; letter-spacing: 2px; }
        .rule { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        .rule-solid { border: 0; border-top: 2px solid #000; margin: 6px 0; }
        .code { font-size: 26px; font-weight: bold; letter-spacing: 4px; margin: 2px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .row > span:first-child { flex: 1; }
        .label { font-size: 10px; text-transform: uppercase; }
        .due { border: 2px solid #000; padding: 4px; margin: 6px 0; text-align: center; }
        .due .date { font-size: 15px; font-weight: bold; }
        ul { margin: 0; padding-left: 14px; }
        .sign { margin-top: 22px; border-top: 1px solid #000; padding-top: 2px; text-align: center; font-size: 10px; }
        .toolbar { width: 72mm; margin: 10px auto; display: flex; gap: 6px; }
        .toolbar button, .toolbar a {
            flex: 1; padding: 8px; font: inherit; font-weight: bold; text-align: center;
            border: 1px solid #000; background: #fff; color: #000; cursor: pointer; text-decoration: none;
        }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print slip</button>
        <a href="{{ route('job-orders.index') }}">Back</a>
    </div>

    <div class="slip">
        <div class="center">
            <div class="shop">{{ config('shop.name') }}</div>
            @if(config('shop.address'))
                <div class="small">{{ config('shop.address') }}</div>
            @endif
            @if(config('shop.phone'))
                <div class="small">Tel: {{ config('shop.phone') }}</div>
            @endif
            <div class="title">TRANSACTION SLIP</div>
            <div class="small">Customer copy</div>
        </div>

        <hr class="rule">

        <div class="center">
            <div class="label">Tracking code</div>
            <div class="code">{{ $jobOrder->tracking_code }}</div>
            <div class="small">Job Order {{ $jobOrder->job_order_number }}</div>
        </div>

        <hr class="rule">

        <div class="row"><span>Received</span><span>{{ $jobOrder->created_at->format('M d, Y h:i A') }}</span></div>
        @if($jobOrder->receivedBy)
            <div class="row"><span>Received by</span><span>{{ $jobOrder->receivedBy->name }}</span></div>
        @endif

        <hr class="rule">

        <div class="label">Customer</div>
        <div class="bold">{{ $jobOrder->customer_name }}</div>
        <div>{{ $jobOrder->customer_phone }}</div>

        <div class="label" style="margin-top: 6px;">Device</div>
        <div class="bold">{{ trim("{$jobOrder->device_brand} {$jobOrder->device_model}") }}</div>
        @if($jobOrder->serial_number)
            <div>S/N / IMEI: {{ $jobOrder->serial_number }}</div>
        @endif

        <div class="label" style="margin-top: 6px;">Problem reported</div>
        <div>{{ \Illuminate\Support\Str::limit($jobOrder->issue_description, 200) }}</div>

        <hr class="rule">

        @foreach($jobOrder->services as $service)
            <div class="row"><span>{{ $service->service_name }}</span><span>{{ number_format($service->labor_price, 2) }}</span></div>
        @endforeach
        @foreach($jobOrder->parts as $part)
            <div class="row">
                <span>{{ $part->part_name }}@if($part->quantity > 1) x{{ $part->quantity }}@endif</span>
                <span>{{ number_format($part->lineTotal(), 2) }}</span>
            </div>
        @endforeach

        <hr class="rule-solid">

        <div class="row bold"><span>ESTIMATED TOTAL</span><span>PHP {{ number_format($total, 2) }}</span></div>
        @if($paid > 0)
            <div class="row"><span>Paid</span><span>{{ number_format($paid, 2) }}</span></div>
            <div class="row bold"><span>Balance</span><span>{{ number_format(max(0, $total - $paid), 2) }}</span></div>
        @endif
        <div class="small">Final amount may change if additional repairs are needed; we will ask you first.</div>

        <div class="due">
            <div class="label">Expected completion</div>
            <div class="date">{{ $jobOrder->expected_completion_date?->format('M d, Y') ?? 'To be advised' }}</div>
        </div>

        <div class="small">
            Track your repair at<br>
            <span class="bold">{{ route('customer.portal.index') }}</span><br>
            using your tracking code.
        </div>

        @if(! empty(config('shop.slip_terms')))
            <hr class="rule">
            <div class="label">Terms</div>
            <ul class="small">
                @foreach(config('shop.slip_terms') as $term)
                    <li>{{ $term }}</li>
                @endforeach
            </ul>
        @endif

        <div class="sign">Customer signature</div>

        <div class="center small" style="margin-top: 8px;">Printed {{ now()->format('M d, Y h:i A') }}</div>
    </div>

    @if($autoPrint)
        <script>
            window.addEventListener('load', function () { window.print(); });
        </script>
    @endif
</body>
</html>
