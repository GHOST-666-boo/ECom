<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .order-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .item { padding: 10px 0; border-bottom: 1px solid #eee; }
        .item:last-child { border-bottom: none; }
        .total { font-size: 18px; font-weight: bold; margin-top: 15px; padding-top: 15px; border-top: 2px solid #4F46E5; }
        .button { display: inline-block; padding: 12px 24px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->user->name }},</p>
            
            <p>Thank you for your order! We have received your order and it is being processed.</p>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Payment Method:</strong> {{ strtoupper($order->payment_method) }}</p>
                
                <h3>Items:</h3>
                @foreach($order->orderItems as $item)
                <div class="item">
                    <strong>{{ $item->product ? $item->product->name : 'Product #' . $item->product_id }}</strong><br>
                    Quantity: {{ $item->quantity }} × ₹{{ number_format($item->price, 2) }} = ₹{{ number_format($item->quantity * $item->price, 2) }}
                </div>
                @endforeach
                
                <div class="total">
                    Total Amount: ₹{{ number_format($order->total, 2) }}
                </div>
            </div>
            
            <p style="text-align: center;">
                <a href="{{ config('app.frontend_url') . '/orders/' . $order->id }}" class="button">View Order</a>
            </p>
            
            <p>We will notify you when your order is shipped.</p>
            <p>Thank you for shopping with us!</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Vriddhi. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
