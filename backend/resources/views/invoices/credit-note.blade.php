<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Note - {{ $creditNote->credit_note_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 20px 28px; }
        .header { display: table; width: 100%; border-bottom: 3px solid #c0392b; padding-bottom: 12px; margin-bottom: 14px; }
        .header-left { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; vertical-align: middle; text-align: right; }
        .brand-name { font-size: 26px; font-weight: bold; color: #2c3e50; letter-spacing: 2px; }
        .cn-title { font-size: 18px; font-weight: bold; color: #c0392b; letter-spacing: 3px; }
        .meta-row { display: table; width: 100%; background: #fdf2f2; border: 1px solid #e74c3c; margin-bottom: 14px; }
        .meta-cell { display: table-cell; padding: 7px 12px; border-right: 1px solid #e74c3c; text-align: center; }
        .meta-cell:last-child { border-right: none; }
        .meta-cell .label { font-size: 9px; color: #999; text-transform: uppercase; }
        .meta-cell .value { font-size: 12px; font-weight: bold; color: #c0392b; }
        .info-grid { display: table; width: 100%; margin-bottom: 12px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; padding: 10px; border: 1px solid #dde1e7; }
        .info-col:first-child { border-right: none; }
        .info-col-head { font-weight: bold; font-size: 9px; letter-spacing: 1px; color: #7f8c8d; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-bottom: 6px; }
        .info-col .label { color: #7f8c8d; font-size: 9px; }
        .summary-box { border: 2px solid #c0392b; padding: 14px; margin-top: 16px; }
        .row { display: table; width: 100%; margin-bottom: 5px; }
        .cell-l { display: table-cell; color: #555; }
        .cell-r { display: table-cell; text-align: right; font-weight: bold; }
        .grand { font-size: 14px; font-weight: bold; color: #c0392b; border-top: 1px solid #ccc; padding-top: 6px; }
        .reason-badge { display: inline-block; background: #e74c3c; color: white; font-size: 9px; padding: 2px 8px; border-radius: 3px; text-transform: uppercase; }
        .footer-note { font-size: 9px; color: #95a5a6; margin-top: 20px; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            @if(file_exists(public_path('logo.png')))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="height: 54px; width: auto; display: block;">
            @else
                <div class="brand-name">VRIDDHI</div>
                <div style="font-size:9px; color:#999; margin-top:2px;">Authentic Indian Handicrafts &amp; Jewellery</div>
            @endif
        </div>
        <div class="header-right">
            <div class="cn-title">CREDIT NOTE</div>
            <div style="margin-top:5px;"><span class="reason-badge">{{ strtoupper($creditNote->reason) }}</span></div>
        </div>
    </div>

    <div class="meta-row">
        <div class="meta-cell">
            <div class="label">Credit Note No.</div>
            <div class="value">{{ $creditNote->credit_note_number }}</div>
        </div>
        <div class="meta-cell">
            <div class="label">Date Issued</div>
            <div class="value">{{ $creditNote->created_at->format('d M Y') }}</div>
        </div>
        <div class="meta-cell">
            <div class="label">Against Invoice</div>
            <div class="value">{{ $creditNote->invoice->invoice_number }}</div>
        </div>
        <div class="meta-cell">
            <div class="label">Order Ref.</div>
            <div class="value">{{ $creditNote->order->order_number ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-col-head">Issued By</div>
            <p><strong>{{ $creditNote->invoice->seller_name }}</strong></p>
            <p style="margin-top:4px;">{{ $creditNote->invoice->seller_address }}</p>
            <p style="margin-top:4px;"><span class="label">GSTIN: </span><strong>{{ $creditNote->invoice->seller_gstin }}</strong></p>
        </div>
        <div class="info-col">
            <div class="info-col-head">Issued To</div>
            <p><strong>{{ $creditNote->invoice->buyer_name }}</strong></p>
            <p style="margin-top:4px;">{{ $creditNote->invoice->buyer_address }}</p>
            <p style="margin-top:4px;"><span class="label">State: </span><strong>{{ $creditNote->invoice->buyer_state }}</strong></p>
            @if($creditNote->invoice->buyer_gstin)
            <p style="margin-top:2px;"><span class="label">GSTIN: </span><strong>{{ $creditNote->invoice->buyer_gstin }}</strong></p>
            @endif
        </div>
    </div>

    <div class="summary-box">
        <p style="font-size:13px; font-weight:bold; margin-bottom:10px; color:#2c3e50;">Credit Note Summary</p>

        <div class="row"><div class="cell-l">Taxable Value (Goods)</div><div class="cell-r">₹{{ number_format($creditNote->subtotal, 2) }}</div></div>
        @if($creditNote->cgst > 0)
        <div class="row"><div class="cell-l">CGST Reversed</div><div class="cell-r">₹{{ number_format($creditNote->cgst, 2) }}</div></div>
        <div class="row"><div class="cell-l">SGST Reversed</div><div class="cell-r">₹{{ number_format($creditNote->sgst, 2) }}</div></div>
        @endif
        @if($creditNote->igst > 0)
        <div class="row"><div class="cell-l">IGST Reversed</div><div class="cell-r">₹{{ number_format($creditNote->igst, 2) }}</div></div>
        @endif

        <div class="row grand">
            <div class="cell-l">TOTAL CREDIT AMOUNT</div>
            <div class="cell-r">₹{{ number_format($creditNote->total_amount, 2) }}</div>
        </div>
    </div>

    <div class="footer-note">
        This Credit Note cancels and supersedes Invoice {{ $creditNote->invoice->invoice_number }}.<br>
        This is a computer generated document. No physical signature required.<br>
        For queries: support@vriddhi.in | www.vriddhi.in
    </div>
</div>
</body>
</html>
