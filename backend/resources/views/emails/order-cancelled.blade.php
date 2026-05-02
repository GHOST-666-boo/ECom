<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #DC2626; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .reason-box { background-color: #fef2f2; padding: 15px; margin: 15px 0; border-left: 4px solid #DC2626; }
        .button { display: inline-block; padding: 12px 24px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Cancelled</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->user->name }},</p>
            
            <p>Your order {{ $order->order_number }} has been cancelled.</p>
            
            <div class="reason-box">
                <h3>Cancellation Reason</h3>
                @if($reason === 'payment timeout')
                <p>Your order was automatically cancelled because payment was not completed within 48 hours.</p>
                <p>If you would still like to purchase these items, please add them to your cart again.</p>
                @else
                <p>{{ ucfirst($reason) }}</p>
                @endif
            </div>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Total Amount:</strong> ₹{{ number_format($order->total, 2) }}</p>
            </div>
            
            <p style="text-align: center;">
                <a href="{{ url('/products') }}" class="button">Browse Products</a>
            </p>
            
            <p>If you have any questions, please don't hesitate to contact us.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Vriddhi. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
