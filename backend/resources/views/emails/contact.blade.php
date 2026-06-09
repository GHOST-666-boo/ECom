<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Inquiry</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4c3e25; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #eee; border-top: none; border-radius: 0 0 5px 5px; }
        .details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border: 1px solid #e5e7eb; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; }
        .details td.label { font-weight: bold; width: 120px; color: #4b5563; }
        .message-box { background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin-top: 10px; border-left: 4px solid #4c3e25; white-space: pre-wrap; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px;">New Inquiry Received</h1>
        </div>
        
        <div class="content">
            <p>Hello Admin,</p>
            <p>You have received a new contact form submission from the <strong>Vriddhi</strong> website.</p>
            
            <div class="details">
                <table>
                    <tr>
                        <td class="label">Name:</td>
                        <td>{{ $inquiry['name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Email:</td>
                        <td><a href="mailto:{{ $inquiry['email'] }}">{{ $inquiry['email'] }}</a></td>
                    </tr>
                    @if(!empty($inquiry['phone']))
                    <tr>
                        <td class="label">Phone:</td>
                        <td>{{ $inquiry['phone'] }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Subject:</td>
                        <td>{{ $inquiry['subject'] }}</td>
                    </tr>
                </table>
            </div>
            
            <h3 style="margin-top: 20px; color: #4c3e25;">Message:</h3>
            <div class="message-box">{{ $inquiry['message'] }}</div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Vriddhi. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
