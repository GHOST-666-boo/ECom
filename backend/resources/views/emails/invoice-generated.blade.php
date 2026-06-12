<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Vriddhi Invoice</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 28px 32px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; letter-spacing: 3px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.8; }
        .body { padding: 32px; }
        .invoice-box { background: #f8f9fa; border-left: 4px solid #2c3e50; padding: 16px 20px; margin: 20px 0; border-radius: 0 6px 6px 0; }
        .invoice-box p { margin: 4px 0; font-size: 14px; }
        .invoice-box .number { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .btn { display: inline-block; background: #2c3e50; color: white; text-decoration: none; padding: 12px 28px; border-radius: 5px; font-size: 14px; font-weight: bold; margin-top: 20px; }
        .footer { background: #f8f9fa; padding: 20px 32px; font-size: 12px; color: #888; text-align: center; border-top: 1px solid #eee; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>VRIDDHI</h1>
            <p>Authentic Indian Handicrafts &amp; Jewellery</p>
        </div>
        <div class="body">
            <p>Dear <strong>{{ $invoice->buyer_name }}</strong>,</p>

            <p>
                Thank you for shopping with Vriddhi! Your order has been delivered successfully.
                Please find your GST Tax Invoice details below.
            </p>

            <div class="invoice-box">
                <p class="number">{{ $invoice->invoice_number }}</p>
                <p>Order: <strong>{{ $orderNumber }}</strong></p>
                <p>Date: <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></p>
                <p>Total: <strong>₹{{ number_format($invoice->total_amount, 2) }}</strong></p>
            </div>

            <p>
                Your invoice PDF is attached to this email for your records.
                You can also download it from your account at any time.
            </p>

            <p>
                If you have any questions about this invoice, please contact us at
                <a href="mailto:support@vriddhi.in">support@vriddhi.in</a>.
            </p>

            <p>Thank you for choosing Vriddhi! 🙏</p>
        </div>
        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message.</p>
            <p>© {{ date('Y') }} Vriddhi. {{ $invoice->seller_address }}</p>
        </div>
    </div>
</body>
</html>
