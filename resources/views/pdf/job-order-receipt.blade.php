<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Invoice - {{ $jobOrder->job_order_number }}</title>

  <style>
    @page { size: 8.5in 11in; margin: 0.5in; }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      color: #222;
      font-size: 11px;
      margin: 0;
      padding: 0;
    }

    .page {
      width: 7.5in;
      margin: 0 auto;
    }

    /* ================= HEADER ================= */

    .top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 14px;
      padding-bottom: 12px;
      border-bottom: 2px solid #ddd;
    }

    .brand {
      font-size: 20px;
      font-weight: 700;
      color: #111;
    }

    .brand-sub {
      font-size: 10px;
      color: #666;
      margin-top: 2px;
    }

    .meta {
      text-align: right;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }

    .meta .title {
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .meta .number {
      font-size: 14px;
      font-weight: 700;
      background: #f2f2f2;
      padding: 4px 8px;
      border-radius: 4px;
      margin-top: 4px;
    }

    .meta .issued {
      font-size: 10px;
      color: #666;
      margin-top: 6px;
    }

    /* ================= COLUMNS ================= */

    .cols {
      display: flex;
      gap: 16px;
      margin: 16px 0;
    }

    .col {
      flex: 1;
    }

    .box {
      border: 1px solid #ddd;
      padding: 12px;
      background: #fafafa;
    }

    .box h4 {
      margin: 0 0 8px 0;
      font-size: 12px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 4px;
    }

    .box-content {
      font-size: 11px;
      line-height: 1.5;
    }

    .small {
      font-size: 10px;
      color: #666;
    }

    /* ================= TABLE ================= */

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    th, td {
      padding: 8px 6px;
      border-bottom: 1px solid #ddd;
    }

    th {
      font-size: 10px;
      background: #f5f5f5;
      font-weight: 600;
      text-align: left;
    }

    td {
      font-size: 11px;
    }

    td.center { text-align: center; }
    td.right { text-align: right; }

    /* ================= TOTAL ================= */

    .total-wrap {
      width: 300px;
      margin-left: auto;
      margin-top: 12px;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 8px;
    }

    .total-row strong {
      font-size: 13px;
    }

    /* ================= FOOTER ================= */

    .notes {
      margin-top: 16px;
      font-size: 10px;
      color: #444;
      padding: 10px;
      background: #fafafa;
      border: 1px solid #ddd;
    }

    .footer {
      margin-top: 20px;
      text-align: center;
      font-size: 10px;
      color: #777;
    }
  </style>
</head>

<body>
<div class="page">

  <!-- HEADER -->
  <div class="top">
    <div>
      <div class="brand">{{ config('app.name', 'IRF-CES') }}</div>
      <div class="brand-sub">Intelligent Repair Flow & Client Engagement System</div>
    </div>

    <div class="meta">
      <div class="title">INVOICE</div>
      <div class="number">#{{ $jobOrder->job_order_number }}</div>
      <div class="issued">Issued: {{ $jobOrder->created_at->format('M d, Y') }}</div>
    </div>
  </div>

  <!-- BILL / DEVICE -->
  <div class="cols">
    <div class="col">
      <div class="box">
        <h4>Bill To</h4>
        <div class="box-content">
          <div style="font-weight:600">{{ $jobOrder->customer_name }}</div>
          <div class="small">{{ $jobOrder->customer_address }}</div>
          <div class="small">{{ $jobOrder->customer_phone }}</div>
          @if($jobOrder->customer_email)
            <div class="small">{{ $jobOrder->customer_email }}</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col">
      <div class="box">
        <h4>Device</h4>
        <div class="box-content">
          <div style="font-weight:600">{{ $jobOrder->device_brand }} {{ $jobOrder->device_model }}</div>
          @if($jobOrder->serial_number)
            <div class="small">Serial / IMEI: {{ $jobOrder->serial_number }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- ITEMS TABLE -->
  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th style="width:60px; text-align:center">Qty</th>
        <th style="width:90px; text-align:right">Price</th>
        <th style="width:90px; text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>

    @php $partsTotal = 0; @endphp

    @foreach($jobOrder->parts_needed ?? [] as $part)
      @php
        $qty = $part['quantity'] ?? 1;
        $unit = $part['unit_sale_price'] ?? 0;
        $line = $qty * $unit;
        $partsTotal += $line;
      @endphp
      <tr>
        <td>{{ $part['part_name'] ?? 'N/A' }}</td>
        <td class="center">{{ $qty }}</td>
        <td class="right">₱{{ number_format($unit, 2) }}</td>
        <td class="right">₱{{ number_format($line, 2) }}</td>
      </tr>
    @endforeach

    @php
      $totalCost = $jobOrder->final_cost ?? $jobOrder->estimated_cost;
      $laborCost = max($totalCost - $partsTotal, 0);

      $serviceDescription = '';
      if(!empty($jobOrder->issues) && count($jobOrder->issues) > 0) {
        $descs = [];
        foreach($jobOrder->issues as $issue) {
          $t = $issue['type'] ?? 'Service';
          if(!empty($issue['diagnosis'])) $t .= ' - '.$issue['diagnosis'];
          $descs[] = $t;
        }
        $serviceDescription = implode(', ', $descs);
      }
    @endphp

    @if($laborCost > 0)
      <tr>
        <td>{{ $serviceDescription ?: 'Labor & Service Fee' }}</td>
        <td class="center">1</td>
        <td class="right">₱{{ number_format($laborCost, 2) }}</td>
        <td class="right">₱{{ number_format($laborCost, 2) }}</td>
      </tr>
    @endif

    </tbody>
  </table>

  <!-- TOTAL -->
  <div class="total-wrap">
    <div class="total-row"><span class="small">Parts</span><span>₱{{ number_format($partsTotal, 2) }}</span></div>
    <div class="total-row"><span class="small">Labor</span><span>₱{{ number_format($laborCost, 2) }}</span></div>
    <div class="total-row" style="border-top:2px solid #333; margin-top:8px">
      <strong>Total</strong>
      <strong>₱{{ number_format($totalCost, 2) }}</strong>
    </div>
  </div>

  <div class="notes">
    <strong>Notes & Terms</strong>
    <div style="margin-top:6px">Payment due upon receipt. Please keep this invoice for your records.</div>
  </div>

  <div class="footer">
    {{ config('app.name', 'IRF-CES') }} — Generated on {{ now()->format('M d, Y') }}
  </div>

</div>
</body>
</html>
