<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply to Your Inquiry - Vriddhi</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4c3e25; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #eee; border-top: none; border-radius: 0 0 5px 5px; }
        .reply-box { background-color: white; padding: 20px; margin: 15px 0; border-radius: 5px; border: 1px solid #e5e7eb; border-left: 4px solid #4c3e25; white-space: pre-wrap; }
        .original-inquiry { font-size: 13px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; }
        .original-message { background-color: #f3f4f6; padding: 10px; border-radius: 5px; margin-top: 5px; white-space: pre-wrap; font-style: italic; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px;">Vriddhi Support</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $inquiry->name }},</p>
            <p>Thank you for contacting Vriddhi. Here is the response to your inquiry regarding "<strong>{{ $inquiry->subject }}</strong>":</p>
            
            <div class="reply-box">{{ $replyMessage }}</div>
            
            <p>If you have any further questions, feel free to reply to this email.</p>
            <p>Best regards,<br>Team Vriddhi</p>
            
            <div class="original-inquiry">
                <strong>Your Original Inquiry:</strong><br>
                <strong>Subject:</strong> {{ $inquiry->subject }}<br>
                <strong>Date:</strong> {{ $inquiry->created_at->format('d M Y, h:i A') }}
                <div class="original-message">{{ $inquiry->message }}</div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Vriddhi. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
