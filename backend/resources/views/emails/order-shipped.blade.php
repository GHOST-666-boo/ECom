<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Shipped</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #10B981; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .address-box { background-color: #f0f9ff; padding: 15px; margin: 15px 0; border-left: 4px solid #10B981; }
        .button { display: inline-block; padding: 12px 24px; background-color: #10B981; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Order Shipped!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->user->name }},</p>
            
            <p>Great news! Your order has been shipped and is on its way to you.</p>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Total Amount:</strong> ₹{{ number_format($order->total, 2) }}</p>
                
                @if($order->tracking_number)
                <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
                @endif
            </div>
            
            <div class="address-box">
                <h3>Delivery Address</h3>
                <p>
                    {{ $order->address_snapshot['name'] }}<br>
                    {{ $order->address_snapshot['line1'] }}<br>
                    @if(!empty($order->address_snapshot['line2']))
                    {{ $order->address_snapshot['line2'] }}<br>
                    @endif
                    {{ $order->address_snapshot['city'] }}, {{ $order->address_snapshot['state'] }} - {{ $order->address_snapshot['pincode'] }}
                </p>
            </div>
            
            <p style="text-align: center;">
                <a href="{{ url('/orders/' . $order->id) }}" class="button">Track Order</a>
            </p>
            
            <p>We will notify you when your order is delivered.</p>
            <p>Thank you for shopping with us!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Artisan Kala. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
