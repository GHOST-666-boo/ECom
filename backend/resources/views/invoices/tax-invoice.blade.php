<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── PAGE LAYOUT ── */
        .page { padding: 20px 28px; }

        /* ── HEADER ── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .header-left  { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; vertical-align: middle; text-align: right; }

        .brand-name {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 2px;
        }
        .brand-tagline { font-size: 9px; color: #7f8c8d; margin-top: 2px; }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #c0392b;
            letter-spacing: 3px;
        }
        .invoice-badge {
            display: inline-block;
            background: #{{ $invoice->invoice_type === 'B2B' ? '2980b9' : '27ae60' }};
            color: white;
            font-size: 9px;
            padding: 2px 7px;
            border-radius: 3px;
            margin-top: 4px;
        }

        /* ── TWO-COLUMN INFO BLOCKS ── */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #dde1e7;
        }
        .info-col:first-child { border-right: none; }

        .info-col-head {
            font-weight: bold;
            font-size: 9px;
            letter-spacing: 1px;
            color: #7f8c8d;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .info-col p { margin-bottom: 2px; line-height: 1.5; }
        .info-col .label { color: #7f8c8d; font-size: 9px; }
        .info-col .value { font-weight: bold; }

        /* ── META ROW ── */
        .meta-row {
            display: table;
            width: 100%;
            background: #f8f9fa;
            border: 1px solid #dde1e7;
            margin-bottom: 12px;
        }
        .meta-cell {
            display: table-cell;
            padding: 7px 12px;
            border-right: 1px solid #dde1e7;
            text-align: center;
        }
        .meta-cell:last-child { border-right: none; }
        .meta-cell .label { font-size: 9px; color: #7f8c8d; text-transform: uppercase; }
        .meta-cell .value { font-size: 12px; font-weight: bold; color: #2c3e50; margin-top: 2px; }

        /* ── ITEMS TABLE ── */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            font-size: 10px;
        }
        table.items thead tr {
            background: #2c3e50;
            color: white;
        }
        table.items thead th {
            padding: 7px 5px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        table.items thead th:first-child { text-align: left; padding-left: 8px; }
        table.items thead th.text-left { text-align: left; }

        table.items tbody tr:nth-child(even) { background: #f8f9fa; }
        table.items tbody td {
            padding: 6px 5px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        table.items tbody td:first-child { text-align: left; padding-left: 8px; }
        table.items tbody td.text-left { text-align: left; }
        table.items tfoot td {
            padding: 5px 5px;
            font-size: 10px;
            border-top: 1px solid #ccc;
        }

        /* ── GST SUMMARY + TOTAL BOX ── */
        .bottom-grid { display: table; width: 100%; margin-top: 0; }
        .bottom-left  { display: table-cell; width: 55%; vertical-align: top; padding-right: 10px; }
        .bottom-right { display: table-cell; width: 45%; vertical-align: top; }

        table.gst-summary {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #dde1e7;
        }
        table.gst-summary thead tr { background: #ecf0f1; }
        table.gst-summary thead th {
            padding: 5px 7px;
            text-align: right;
            font-size: 9px;
            color: #2c3e50;
            font-weight: bold;
            border: 1px solid #dde1e7;
        }
        table.gst-summary thead th:first-child { text-align: left; }
        table.gst-summary tbody td {
            padding: 5px 7px;
            text-align: right;
            border: 1px solid #eee;
        }
        table.gst-summary tbody td:first-child { text-align: left; }
        table.gst-summary tfoot td {
            padding: 5px 7px;
            font-weight: bold;
            background: #ecf0f1;
            border: 1px solid #dde1e7;
            text-align: right;
        }
        table.gst-summary tfoot td:first-child { text-align: left; }

        .total-box {
            border: 2px solid #2c3e50;
            padding: 10px 14px;
        }
        .total-row { display: table; width: 100%; margin-bottom: 4px; }
        .total-label { display: table-cell; color: #555; font-size: 10px; }
        .total-value { display: table-cell; text-align: right; font-size: 10px; font-weight: bold; }
        .total-row.grand .total-label,
        .total-row.grand .total-value {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }

        /* ── AMOUNT IN WORDS ── */
        .words-box {
            margin-top: 8px;
            background: #fef9e7;
            border: 1px solid #f1c40f;
            padding: 6px 10px;
            font-size: 10px;
            border-radius: 2px;
        }
        .words-box .label { font-size: 9px; color: #7f8c8d; }

        /* ── FOOTER ── */
        .footer {
            margin-top: 16px;
            border-top: 1px solid #dde1e7;
            padding-top: 10px;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; width: 60%; vertical-align: bottom; }
        .footer-right { display: table-cell; width: 40%; text-align: right; vertical-align: bottom; }
        .footer-note { font-size: 9px; color: #95a5a6; }
        .sig-line { border-top: 1px solid #aaa; width: 140px; margin: 30px 0 4px auto; }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="header">
        <div class="header-left">
            @if(file_exists(public_path('logo.png')))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="height: 54px; width: auto; display: block;">
            @else
                <div class="brand-name">VRIDDHI</div>
                <div class="brand-tagline">Authentic Indian Handicrafts &amp; Jewellery</div>
            @endif
        </div>
        <div class="header-right">
            <div class="invoice-title">TAX INVOICE</div>
            <div class="invoice-badge">{{ $invoice->invoice_type }}</div>
        </div>
    </div>

    {{-- ══════════ META ROW ══════════ --}}
    <div class="meta-row">
        <div class="meta-cell">
            <div class="label">Invoice Number</div>
            <div class="value">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="meta-cell">
            <div class="label">Invoice Date</div>
            <div class="value">{{ $invoice->invoice_date->format('d M Y') }}</div>
        </div>
        <div class="meta-cell">
            <div class="label">Order Reference</div>
            <div class="value">{{ $invoice->order->order_number ?? 'N/A' }}</div>
        </div>
        <div class="meta-cell">
            <div class="label">Payment</div>
            <div class="value" style="text-transform:uppercase;">{{ $invoice->order->payment_method ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- ══════════ SELLER / BUYER INFO ══════════ --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-col-head">Sold By (Supplier)</div>
            <p class="value" style="font-size:13px;">{{ $invoice->seller_name }}</p>
            <p style="margin-top:4px;">{{ $invoice->seller_address }}</p>
            <p style="margin-top:4px;"><span class="label">State: </span><strong>{{ $invoice->seller_state }}</strong></p>
            <p style="margin-top:2px;"><span class="label">GSTIN: </span><strong>{{ $invoice->seller_gstin }}</strong></p>
        </div>
        <div class="info-col">
            <div class="info-col-head">Billed To (Recipient)</div>
            <p class="value" style="font-size:13px;">{{ $invoice->buyer_name }}</p>
            <p style="margin-top:4px;">{{ $invoice->buyer_address }}</p>
            <p style="margin-top:4px;"><span class="label">State: </span><strong>{{ $invoice->buyer_state }}</strong></p>
            @if($invoice->buyer_gstin)
            <p style="margin-top:2px;"><span class="label">GSTIN: </span><strong>{{ $invoice->buyer_gstin }}</strong></p>
            @endif
        </div>
    </div>

    {{-- ══════════ LINE ITEMS TABLE ══════════ --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th class="text-left" style="width:24%;">Product Description</th>
                <th style="width:8%;">HSN/SAC</th>
                <th style="width:5%;">Qty</th>
                <th style="width:9%;">Unit Price (₹)</th>
                <th style="width:9%;">Taxable Value (₹)</th>
                <th style="width:6%;">CGST %</th>
                <th style="width:8%;">CGST (₹)</th>
                <th style="width:6%;">SGST %</th>
                <th style="width:8%;">SGST (₹)</th>
                <th style="width:6%;">IGST %</th>
                <th style="width:8%;">IGST (₹)</th>
                <th style="width:9%;">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $item->product_name }}</td>
                <td>{{ $item->hsn_code }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->taxable_value, 2) }}</td>
                <td>{{ $item->cgst_amount > 0 ? number_format($item->gst_rate / 2, 2) . '%' : '—' }}</td>
                <td>{{ $item->cgst_amount > 0 ? number_format($item->cgst_amount, 2) : '—' }}</td>
                <td>{{ $item->sgst_amount > 0 ? number_format($item->gst_rate / 2, 2) . '%' : '—' }}</td>
                <td>{{ $item->sgst_amount > 0 ? number_format($item->sgst_amount, 2) : '—' }}</td>
                <td>{{ $item->igst_amount > 0 ? number_format($item->gst_rate, 2) . '%' : '—' }}</td>
                <td>{{ $item->igst_amount > 0 ? number_format($item->igst_amount, 2) : '—' }}</td>
                <td><strong>{{ number_format($item->line_total, 2) }}</strong></td>
            </tr>
            @endforeach

            {{-- Shipping row if applicable --}}
            @if($invoice->shipping_amount > 0)
            <tr style="background:#fff9f0;">
                <td>—</td>
                <td class="text-left">Shipping Charges <small>(SAC {{ config('invoice.shipping_sac') }})</small></td>
                <td>{{ config('invoice.shipping_sac') }}</td>
                <td>1</td>
                <td>{{ number_format($invoice->shipping_amount, 2) }}</td>
                <td>{{ number_format($invoice->shipping_amount, 2) }}</td>
                <td>{{ $invoice->shipping_cgst > 0 ? number_format($invoice->shipping_gst_rate / 2, 2) . '%' : '—' }}</td>
                <td>{{ $invoice->shipping_cgst > 0 ? number_format($invoice->shipping_cgst, 2) : '—' }}</td>
                <td>{{ $invoice->shipping_sgst > 0 ? number_format($invoice->shipping_gst_rate / 2, 2) . '%' : '—' }}</td>
                <td>{{ $invoice->shipping_sgst > 0 ? number_format($invoice->shipping_sgst, 2) : '—' }}</td>
                <td>{{ $invoice->shipping_igst > 0 ? number_format($invoice->shipping_gst_rate, 2) . '%' : '—' }}</td>
                <td>{{ $invoice->shipping_igst > 0 ? number_format($invoice->shipping_igst, 2) : '—' }}</td>
                <td><strong>{{ number_format($invoice->shipping_amount + $invoice->shipping_cgst + $invoice->shipping_sgst + $invoice->shipping_igst, 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right; font-weight:bold;">Subtotal</td>
                <td style="text-align:center; font-weight:bold;">{{ number_format($invoice->subtotal, 2) }}</td>
                <td></td>
                <td style="text-align:center; font-weight:bold;">{{ number_format($invoice->cgst + $invoice->shipping_cgst, 2) }}</td>
                <td></td>
                <td style="text-align:center; font-weight:bold;">{{ number_format($invoice->sgst + $invoice->shipping_sgst, 2) }}</td>
                <td></td>
                <td style="text-align:center; font-weight:bold;">{{ number_format($invoice->igst + $invoice->shipping_igst, 2) }}</td>
                <td style="text-align:center; font-weight:bold; color:#c0392b;">{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ══════════ BOTTOM: GST SUMMARY + TOTALS ══════════ --}}
    <div class="bottom-grid" style="margin-top:12px;">

        {{-- Rate-wise GST Summary --}}
        <div class="bottom-left">
            <div class="section-title">GST Rate-wise Summary</div>
            <table class="gst-summary">
                <thead>
                    <tr>
                        <th>GST Rate</th>
                        <th>Taxable Value (₹)</th>
                        <th>CGST (₹)</th>
                        <th>SGST (₹)</th>
                        <th>IGST (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gstSummary as $group)
                    <tr>
                        <td>{{ isset($group['label']) ? $group['label'] : $group['gst_rate'] . '%' }}</td>
                        <td>{{ number_format($group['taxable_value'], 2) }}</td>
                        <td>{{ $group['cgst'] > 0 ? number_format($group['cgst'], 2) : '—' }}</td>
                        <td>{{ $group['sgst'] > 0 ? number_format($group['sgst'], 2) : '—' }}</td>
                        <td>{{ $group['igst'] > 0 ? number_format($group['igst'], 2) : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>TOTAL</strong></td>
                        <td><strong>{{ number_format($invoice->subtotal + $invoice->shipping_amount, 2) }}</strong></td>
                        <td><strong>{{ ($invoice->cgst + $invoice->shipping_cgst) > 0 ? number_format($invoice->cgst + $invoice->shipping_cgst, 2) : '—' }}</strong></td>
                        <td><strong>{{ ($invoice->sgst + $invoice->shipping_sgst) > 0 ? number_format($invoice->sgst + $invoice->shipping_sgst, 2) : '—' }}</strong></td>
                        <td><strong>{{ ($invoice->igst + $invoice->shipping_igst) > 0 ? number_format($invoice->igst + $invoice->shipping_igst, 2) : '—' }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            {{-- Amount in words --}}
            <div class="words-box" style="margin-top:8px;">
                <div class="label">AMOUNT IN WORDS:</div>
                <strong>{{ $totalInWords }}</strong>
            </div>
        </div>

        {{-- Grand Total Box --}}
        <div class="bottom-right">
            <div class="section-title">Invoice Summary</div>
            <div class="total-box">
                <div class="total-row">
                    <div class="total-label">Product Subtotal</div>
                    <div class="total-value">₹{{ number_format($invoice->subtotal, 2) }}</div>
                </div>
                @if($invoice->cgst > 0)
                <div class="total-row">
                    <div class="total-label">CGST</div>
                    <div class="total-value">₹{{ number_format($invoice->cgst, 2) }}</div>
                </div>
                <div class="total-row">
                    <div class="total-label">SGST</div>
                    <div class="total-value">₹{{ number_format($invoice->sgst, 2) }}</div>
                </div>
                @endif
                @if($invoice->igst > 0)
                <div class="total-row">
                    <div class="total-label">IGST</div>
                    <div class="total-value">₹{{ number_format($invoice->igst, 2) }}</div>
                </div>
                @endif
                @if($invoice->shipping_amount > 0)
                <div class="total-row">
                    <div class="total-label">Shipping</div>
                    <div class="total-value">₹{{ number_format($invoice->shipping_amount, 2) }}</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Shipping GST</div>
                    <div class="total-value">₹{{ number_format($invoice->shipping_cgst + $invoice->shipping_sgst + $invoice->shipping_igst, 2) }}</div>
                </div>
                @endif
                <div class="total-row grand">
                    <div class="total-label">GRAND TOTAL</div>
                    <div class="total-value">₹{{ number_format($invoice->total_amount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ FOOTER ══════════ --}}
    <div class="footer">
        <div class="footer-left">
            <p class="footer-note">
                This is a computer generated invoice. No physical signature required.<br>
                For support, contact: support@vriddhi.in | www.vriddhi.in
            </p>
            <p class="footer-note" style="margin-top:6px;">
                Subject to New Delhi jurisdiction. Goods once sold will not be taken back or exchanged.
            </p>
        </div>
        <div class="footer-right">
            <div class="sig-line"></div>
            <p style="font-size:10px; color:#2c3e50; font-weight:bold;">For {{ $invoice->seller_name }}</p>
            <p class="footer-note">Authorised Signatory</p>
        </div>
    </div>

</div>
</body>
</html>
